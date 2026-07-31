<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Wallet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $office = $request->user()->office;
        if (! $office) {
            return response()->json(['message' => 'دفتر یافت نشد.'], 404);
        }

        $wallet = Wallet::firstOrCreate(['office_id' => $office->id], ['balance' => 0]);

        return response()->json([
            'data' => [
                'balance' => $wallet->balance,
                'transactions' => $wallet->transactions()->latest()->limit(20)->get([
                    'id', 'type', 'amount', 'balance_after', 'description', 'created_at',
                ]),
            ],
        ]);
    }
}
