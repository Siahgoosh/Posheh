<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
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

        return response()->json($query->paginate(20));
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
        $data = $request->validate([
            'amount' => ['required', 'integer'],
            'type' => ['required', 'in:credit,debit'],
            'description' => ['required', 'string', 'max:500'],
        ]);

        $wallet = Wallet::firstOrCreate(['office_id' => $officeId], ['balance' => 0]);

        return DB::transaction(function () use ($wallet, $data, $officeId) {
            $amount = abs($data['amount']);
            if ($data['type'] === 'debit' && $wallet->balance < $amount) {
                return response()->json(['message' => 'موجودی کافی نیست.'], 422);
            }

            if ($data['type'] === 'credit') {
                $wallet->increment('balance', $amount);
            } else {
                $wallet->decrement('balance', $amount);
            }

            $wallet->transactions()->create([
                'type' => $data['type'] === 'credit' ? 'credit' : 'debit',
                'amount' => $amount,
                'balance_after' => $wallet->balance,
                'description' => $data['description'].' (مدیر سیستم)',
            ]);

            $this->audit->log('wallet.adjusted', Wallet::class, $wallet->id, $data['description'], null, $data);

            return response()->json(['data' => $wallet->fresh()]);
        });
    }
}
