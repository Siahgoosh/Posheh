<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Office;
use App\Services\Wallet\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminOfficeWalletController extends Controller
{
    public function __construct(private readonly WalletService $wallet) {}

    public function show(int $id): JsonResponse
    {
        $office = Office::with('wallet.transactions')->findOrFail($id);

        return response()->json([
            'data' => [
                'office_id' => $office->id,
                'office_name' => $office->name,
                'balance' => (int) ($office->wallet?->balance ?? 0),
                'transactions' => $office->wallet?->transactions()
                    ->latest()
                    ->limit(30)
                    ->get()
                    ->map(fn ($tx) => [
                        'id' => $tx->id,
                        'type' => $tx->type,
                        'type_label' => $tx->type === 'credit' ? 'واریز' : 'برداشت',
                        'amount' => (int) $tx->amount,
                        'balance_after' => (int) $tx->balance_after,
                        'description' => $tx->description,
                        'created_at' => $tx->created_at?->toIso8601String(),
                    ]) ?? [],
            ],
        ]);
    }

    public function credit(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'integer', 'min:1000', 'max:50000000'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $office = Office::findOrFail($id);

        return response()->json([
            'data' => $this->wallet->adminCredit(
                $office,
                $request->user(),
                (int) $data['amount'],
                $data['description'] ?? 'شارژ دستی توسط مدیر سیستم',
            ),
            'message' => 'کیف پول دفتر با موفقیت شارژ شد.',
        ]);
    }

    public function debit(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'integer', 'min:1000', 'max:50000000'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $office = Office::findOrFail($id);

        return response()->json([
            'data' => $this->wallet->adminDebit(
                $office,
                $request->user(),
                (int) $data['amount'],
                $data['description'] ?? 'برداشت دستی توسط مدیر سیستم',
            ),
            'message' => 'مبلغ از کیف پول دفتر کسر شد.',
        ]);
    }
}
