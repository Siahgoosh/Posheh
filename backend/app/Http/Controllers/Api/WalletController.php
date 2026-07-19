<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Wallet\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function __construct(private readonly WalletService $wallet) {}

    public function balance(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->wallet->balance($request->user()->office)]);
    }

    public function topUp(Request $request): JsonResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'integer', 'min:10000', 'max:50000000'],
        ]);

        return response()->json(
            $this->wallet->initiateTopUp($request->user()->office, $request->user(), (int) $data['amount'])
        );
    }
}
