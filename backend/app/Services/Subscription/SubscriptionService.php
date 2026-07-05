<?php

namespace App\Services\Subscription;

use App\Enums\PaymentGateway;
use App\Models\Office;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Wallet;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SubscriptionService
{
    public function getPlans()
    {
        return SubscriptionPlan::where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }

    public function subscribe(Office $office, int $planId, PaymentGateway $gateway): array
    {
        $plan = SubscriptionPlan::findOrFail($planId);

        $payment = Payment::create([
            'office_id' => $office->id,
            'gateway' => $gateway,
            'status' => 'pending',
            'amount' => $plan->monthly_price,
            'authority' => Str::uuid()->toString(),
        ]);

        if ($gateway === PaymentGateway::Wallet) {
            return $this->payWithWallet($office, $plan, $payment);
        }

        if ($gateway === PaymentGateway::ZarinPal) {
            return $this->initiateZarinPal($payment);
        }

        return [
            'payment_id' => $payment->id,
            'gateway' => $gateway->value,
            'amount' => $payment->amount,
            'message' => 'پرداخت درگاه کافه‌بازار آماده است.',
        ];
    }

    public function verifyZarinPal(string $authority, string $status): array
    {
        $payment = Payment::where('authority', $authority)->firstOrFail();

        if ($status !== 'OK') {
            $payment->update(['status' => 'failed']);

            throw ValidationException::withMessages([
                'payment' => ['پرداخت ناموفق بود.'],
            ]);
        }

        $payment->update([
            'status' => 'paid',
            'ref_id' => Str::random(10),
            'paid_at' => now(),
        ]);

        $this->activateSubscription($payment);

        return ['message' => 'پرداخت با موفقیت انجام شد.', 'payment' => $payment];
    }

    private function payWithWallet(Office $office, SubscriptionPlan $plan, Payment $payment): array
    {
        $wallet = $office->wallet ?? Wallet::create(['office_id' => $office->id, 'balance' => 0]);

        if ($wallet->balance < $plan->monthly_price) {
            throw ValidationException::withMessages([
                'wallet' => ['موجودی کیف پول کافی نیست.'],
            ]);
        }

        $wallet->decrement('balance', $plan->monthly_price);
        $wallet->transactions()->create([
            'type' => 'debit',
            'amount' => $plan->monthly_price,
            'balance_after' => $wallet->balance,
            'description' => "خرید اشتراک {$plan->name}",
            'reference_type' => Payment::class,
            'reference_id' => $payment->id,
        ]);

        $payment->update(['status' => 'paid', 'paid_at' => now()]);
        $this->activateSubscription($payment);

        return ['message' => 'اشتراک با موفقیت فعال شد.', 'payment' => $payment];
    }

    private function initiateZarinPal(Payment $payment): array
    {
        $merchantId = config('services.zarinpal.merchant_id');
        $callbackUrl = config('app.url').'/api/v1/payments/zarinpal/callback';

        return [
            'payment_id' => $payment->id,
            'gateway' => 'zarinpal',
            'amount' => $payment->amount,
            'redirect_url' => "https://www.zarinpal.com/pg/StartPay/{$payment->authority}",
            'authority' => $payment->authority,
        ];
    }

    private function activateSubscription(Payment $payment): Subscription
    {
        $plan = SubscriptionPlan::first();

        Subscription::where('office_id', $payment->office_id)
            ->where('status', 'active')
            ->update(['status' => 'expired']);

        return Subscription::create([
            'office_id' => $payment->office_id,
            'subscription_plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
            'auto_renew' => true,
        ]);
    }
}
