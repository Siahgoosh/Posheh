<?php

namespace App\Services\Wallet;

use App\Enums\PaymentGateway;
use App\Models\Office;
use App\Models\Payment;
use App\Models\User;
use App\Models\Wallet;
use App\Services\Payment\ZibalService;
use App\Services\Payment\PaymentInvoiceService;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class WalletService
{
    public function __construct(
        private readonly ZibalService $zibal,
    ) {}

    public function balance(Office $office): array
    {
        $wallet = $office->wallet ?? Wallet::create(['office_id' => $office->id, 'balance' => 0]);

        return [
            'balance' => (int) $wallet->balance,
            'currency' => 'IRT',
        ];
    }

    public function initiateTopUp(Office $office, User $user, int $amountToman): array
    {
        if ($amountToman < 10_000) {
            throw ValidationException::withMessages([
                'amount' => ['حداقل مبلغ شارژ ۱۰٬۰۰۰ تومان است.'],
            ]);
        }

        $payment = Payment::create([
            'office_id' => $office->id,
            'user_id' => $user->id,
            'user_phone' => $user->mobile,
            'gateway' => PaymentGateway::Zibal,
            'status' => 'pending',
            'amount' => $amountToman,
            'original_amount' => $amountToman,
            'discount_amount' => 0,
            'authority' => 'pending',
            'metadata' => [
                'purpose' => 'wallet_topup',
                'user_name' => $user->name,
            ],
        ]);

        $appUrl = rtrim(config('app.url'), '/');
        $callbackUrl = $appUrl.'/api/v1/payments/zibal/callback';

        $result = $this->zibal->request(
            $amountToman,
            'شارژ کیف پول — پوشه',
            $callbackUrl,
            $payment->id,
            $user->mobile,
        );

        $payment->update(['authority' => $result['track_id']]);

        return [
            'payment_id' => $payment->id,
            'amount' => $amountToman,
            'redirect_url' => $result['redirect_url'],
            'track_id' => $result['track_id'],
            'invoice' => app(PaymentInvoiceService::class)->build($payment->fresh()),
        ];
    }

    public function adminCredit(Office $office, User $admin, int $amountToman, string $description): array
    {
        $wallet = $office->wallet ?? Wallet::create(['office_id' => $office->id, 'balance' => 0]);

        $payment = Payment::create([
            'office_id' => $office->id,
            'user_id' => $admin->id,
            'gateway' => PaymentGateway::Manual,
            'status' => 'paid',
            'amount' => $amountToman,
            'original_amount' => $amountToman,
            'paid_at' => now(),
            'metadata' => [
                'purpose' => 'manual_credit',
                'description' => $description,
                'admin_user_id' => $admin->id,
                'admin_name' => $admin->name,
            ],
        ]);

        $wallet->increment('balance', $amountToman);
        $wallet->transactions()->create([
            'type' => 'credit',
            'amount' => $amountToman,
            'balance_after' => $wallet->balance,
            'description' => $description,
            'reference_type' => Payment::class,
            'reference_id' => $payment->id,
        ]);

        return [
            'balance' => (int) $wallet->balance,
            'payment_id' => $payment->id,
        ];
    }

    public function adminDebit(Office $office, User $admin, int $amountToman, string $description): array
    {
        $wallet = $office->wallet ?? Wallet::create(['office_id' => $office->id, 'balance' => 0]);

        if ($wallet->balance < $amountToman) {
            throw ValidationException::withMessages([
                'amount' => ['موجودی کیف پول کافی نیست.'],
            ]);
        }

        $wallet->decrement('balance', $amountToman);
        $wallet->transactions()->create([
            'type' => 'debit',
            'amount' => $amountToman,
            'balance_after' => $wallet->balance,
            'description' => $description,
            'reference_type' => User::class,
            'reference_id' => $admin->id,
        ]);

        return [
            'balance' => (int) $wallet->balance,
        ];
    }

    public function completeTopUp(Payment $payment): array
    {
        $wallet = $payment->office->wallet ?? Wallet::create(['office_id' => $payment->office_id, 'balance' => 0]);
        $wallet->increment('balance', $payment->amount);
        $wallet->transactions()->create([
            'type' => 'credit',
            'amount' => $payment->amount,
            'balance_after' => $wallet->balance,
            'description' => 'شارژ کیف پول',
            'reference_type' => Payment::class,
            'reference_id' => $payment->id,
        ]);

        return [
            'message' => 'کیف پول با موفقیت شارژ شد.',
            'balance' => (int) $wallet->balance,
            'payment' => $payment,
            'success' => true,
        ];
    }
}
