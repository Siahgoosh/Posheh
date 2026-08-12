<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Office;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\Admin\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
        $query = WalletTransaction::with(['wallet.office:id,name'])
            ->when($request->filled('office_id'), function ($q) use ($request) {
                $q->whereHas('wallet', fn ($w) => $w->where('office_id', $request->integer('office_id')));
            })
            ->latest();

        return response()->json($query->paginate(30));
    }

    public function adjust(Request $request, int $officeId): JsonResponse
    {
        Office::query()->findOrFail($officeId);

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
            'type' => ['required', 'in:credit,debit'],
            'description' => ['required', 'string', 'max:500'],
        ]);

        $amount = (int) abs((float) $data['amount']);
        if ($amount < 1) {
            return response()->json(['message' => 'مبلغ باید حداقل ۱ تومان باشد.'], 422);
        }

        try {
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

                $wallet->transactions()->create([
                    'type' => $data['type'] === 'credit' ? 'credit' : 'debit',
                    'amount' => $amount,
                    'balance_after' => (int) $wallet->balance,
                    'description' => $data['description'].' (مدیر سیستم)',
                ]);

                return $wallet->fresh('office');
            });
        } catch (\RuntimeException $e) {
            if ($e->getMessage() === 'INSUFFICIENT_BALANCE') {
                return response()->json(['message' => 'موجودی کافی نیست.'], 422);
            }
            throw $e;
        }

        // Outside the money transaction so audit schema issues cannot roll back the charge.
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
}
