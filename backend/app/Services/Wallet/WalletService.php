<?php

namespace App\Services\Wallet;

use App\Enums\PaymentGateway;
use App\Models\Office;
use App\Models\Payment;
use App\Models\Wallet;
use App\Services\Payment\ZibalService;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class WalletService
{
    public function __construct(private readonly ZibalService $zibal) {}

    public function initiateTopUp(Office $office, int $amount): array
    {
        if ($amount < 100_000) {
            throw ValidationException::withMessages([
                'amount' => ['حداقل مبلغ شارژ ۱۰۰٬۰۰۰ تومان است.'],
            ]);
        }

        if ($amount > 50_000_000) {
            throw ValidationException::withMessages([
                'amount' => ['حداکثر مبلغ شارژ ۵۰٬۰۰۰٬۰۰۰ تومان است.'],
            ]);
        }

        $payment = Payment::create([
            'office_id' => $office->id,
            'gateway' => PaymentGateway::Zibal,
            'status' => 'pending',
            'amount' => $amount,
            'authority' => 'pending',
            'metadata' => ['type' => 'wallet_topup'],
        ]);

        $appUrl = rtrim(config('app.url'), '/');
        $result = $this->zibal->request(
            $amount,
            'شارژ کیف پول پوشه',
            $appUrl.'/api/v1/payments/zibal/callback',
            $payment->id,
        );

        $payment->update(['authority' => $result['track_id']]);

        return [
            'payment_id' => $payment->id,
            'amount' => $amount,
            'redirect_url' => $result['redirect_url'],
        ];
    }

    public function creditFromPayment(Payment $payment): Wallet
    {
        $office = $payment->office;
        $wallet = $office->wallet ?? Wallet::create(['office_id' => $office->id, 'balance' => 0]);

        $wallet->increment('balance', $payment->amount);
        $wallet->refresh();

        $wallet->transactions()->create([
            'type' => 'credit',
            'amount' => $payment->amount,
            'balance_after' => $wallet->balance,
            'description' => 'شارژ کیف پول از درگاه زیبال',
            'reference_type' => Payment::class,
            'reference_id' => $payment->id,
        ]);

        return $wallet;
    }
}
