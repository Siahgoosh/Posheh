<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Office;
use App\Models\Wallet;
use App\Services\Admin\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

class AdminWalletController extends Controller
{
    public function __construct(private readonly AuditLogService $audit) {}

    public function index(Request $request): JsonResponse
    {
        $query = Wallet::with('office:id,name,slug')
            ->when($request->filled('office_id'), fn ($q) => $q->where('office_id', $request->integer('office_id')))
            ->orderByDesc('balance');

        return response()->json($query->paginate(50));
    }

    public function transactions(Request $request): JsonResponse
    {
        $query = \App\Models\WalletTransaction::with(['wallet.office:id,name'])
            ->when($request->filled('office_id'), function ($q) use ($request) {
                $q->whereHas('wallet', fn ($w) => $w->where('office_id', $request->integer('office_id')));
            })
            ->latest();

        return response()->json($query->paginate(30));
    }

    public function adjust(Request $request, int $officeId): JsonResponse
    {
        try {
            if (! Schema::hasTable('wallets') || ! Schema::hasTable('wallet_transactions')) {
                return response()->json([
                    'message' => 'جدول کیف پول در دیتابیس وجود ندارد. لطفاً migrate را اجرا کنید.',
                    'code' => 'schema_outdated',
                ], 500);
            }

            Office::query()->findOrFail($officeId);

            $data = $request->validate([
                'amount' => ['required', 'numeric', 'min:1'],
                'type' => ['required', 'in:credit,debit'],
                // DB column is varchar(255); keep room for " (مدیر سیستم)"
                'description' => ['required', 'string', 'max:200'],
            ]);

            $amount = (int) abs((float) $data['amount']);
            if ($amount < 1) {
                return response()->json(['message' => 'مبلغ باید حداقل ۱ تومان باشد.'], 422);
            }

            $wallet = DB::transaction(function () use ($officeId, $data, $amount) {
                $wallet = Wallet::query()->firstOrCreate(
                    ['office_id' => $officeId],
                    ['balance' => 0]
                );

                $wallet = Wallet::query()->whereKey($wallet->id)->lockForUpdate()->firstOrFail();

                if ($data['type'] === 'debit' && (int) $wallet->balance < $amount) {
                    throw new \RuntimeException('INSUFFICIENT_BALANCE');
                }

                if ($data['type'] === 'credit') {
                    $wallet->increment('balance', $amount);
                } else {
                    $wallet->decrement('balance', $amount);
                }

                $wallet->refresh();

                $suffix = ' (مدیر سیستم)';
                $baseDesc = mb_substr((string) $data['description'], 0, 255 - mb_strlen($suffix));

                $wallet->transactions()->create([
                    'type' => $data['type'] === 'credit' ? 'credit' : 'debit',
                    'amount' => $amount,
                    'balance_after' => (int) $wallet->balance,
                    'description' => $baseDesc.$suffix,
                ]);

                return $wallet->fresh('office');
            });
        } catch (\RuntimeException $e) {
            if ($e->getMessage() === 'INSUFFICIENT_BALANCE') {
                return response()->json(['message' => 'موجودی کافی نیست.'], 422);
            }

            return $this->fail('کیف پول', $e);
        } catch (Throwable $e) {
            return $this->fail('کیف پول', $e);
        }

        $this->audit->log(
            'wallet.adjusted',
            Wallet::class,
            $wallet->id,
            $data['description'],
            null,
            ['type' => $data['type'], 'amount' => $amount, 'office_id' => $officeId]
        );

        return response()->json([
            'data' => $wallet,
            'message' => $data['type'] === 'credit' ? 'کیف پول با موفقیت شارژ شد.' : 'برداشت با موفقیت انجام شد.',
        ]);
    }

    private function fail(string $op, Throwable $e): JsonResponse
    {
        Log::error("admin.{$op}.failed", [
            'error' => $e->getMessage(),
            'class' => $e::class,
        ]);

        $hint = $this->schemaHint($e->getMessage());

        return response()->json([
            'message' => $hint ?: ("خطا در عملیات {$op}: ".$e->getMessage()),
            'code' => $hint ? 'schema_outdated' : class_basename($e),
            'error' => $e->getMessage(),
        ], 500);
    }

    private function schemaHint(string $message): ?string
    {
        $m = mb_strtolower($message);
        if (str_contains($m, 'audit_logs') || str_contains($m, 'unknown column') || str_contains($m, 'base table or view not found')) {
            return 'اسکیمای دیتابیس قدیمی است. روی سرور اجرا کنید: ./scripts/deploy.sh cursor/production-release-a876';
        }

        return null;
    }
}
