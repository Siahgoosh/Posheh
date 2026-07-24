<?php

namespace App\Services\Subscription;

use App\Enums\PaymentGateway;
use App\Models\Office;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Wallet;
use App\Services\Payment\CafeBazaarService;
use App\Services\Payment\ZibalService;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SubscriptionService
{
    public function __construct(
        private readonly ZibalService $zibal,
        private readonly CafeBazaarService $cafeBazaar,
    ) {}

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
            'authority' => 'pending',
            'metadata' => ['plan_id' => $plan->id],
        ]);

        if ($gateway === PaymentGateway::Wallet) {
            return $this->payWithWallet($office, $plan, $payment);
        }

        if ($gateway === PaymentGateway::Zibal) {
            return $this->initiateZibal($payment, $plan);
        }

        return [
            'payment_id' => $payment->id,
            'gateway' => $gateway->value,
            'amount' => $payment->amount,
            'message' => 'پرداخت درگاه کافه‌بازار آماده است.',
        ];
    }

    public function verifyCafeBazaarPurchase(
        Office $office,
        int $planId,
        string $productId,
        string $purchaseToken,
        ?string $orderId = null,
    ): array {
        $plan = SubscriptionPlan::findOrFail($planId);

        if ($plan->slug !== $productId) {
            throw ValidationException::withMessages([
                'product_id' => ['شناسه محصول با پلن انتخاب‌شده مطابقت ندارد.'],
            ]);
        }

        $existing = Payment::where('gateway', PaymentGateway::CafeBazaar)
            ->where('authority', $purchaseToken)
            ->where('status', 'paid')
            ->first();

        if ($existing) {
            return [
                'message' => 'این خرید قبلاً ثبت شده است.',
                'payment' => $existing,
                'success' => true,
            ];
        }

        $packageName = config('services.cafe_bazaar.package_name', 'ir.posheapp.posheh');
        $this->cafeBazaar->verifySubscriptionPurchase($packageName, $productId, $purchaseToken);

        $payment = Payment::create([
            'office_id' => $office->id,
            'gateway' => PaymentGateway::CafeBazaar,
            'status' => 'paid',
            'amount' => $plan->monthly_price,
            'authority' => $purchaseToken,
            'ref_id' => $orderId,
            'transaction_id' => $orderId,
            'metadata' => ['plan_id' => $plan->id, 'product_id' => $productId],
            'paid_at' => now(),
        ]);

        $subscription = $this->activateSubscription($payment);

        return [
            'message' => 'اشتراک با موفقیت فعال شد.',
            'payment' => $payment,
            'subscription' => $subscription,
            'success' => true,
        ];
    }

    public function verifyZibal(string $trackId, bool $success): array
    {
        $payment = Payment::where('authority', $trackId)->firstOrFail();

        if ($payment->status === 'paid') {
            return ['message' => 'این پرداخت قبلاً تأیید شده است.', 'payment' => $payment, 'success' => true];
        }

        if (! $success) {
            $payment->update(['status' => 'failed']);

            throw ValidationException::withMessages([
                'payment' => ['پرداخت ناموفق بود یا توسط کاربر لغو شد.'],
            ]);
        }

        $verify = $this->zibal->verify($trackId, (int) $payment->amount);

        if (! $verify['success']) {
            $payment->update(['status' => 'failed']);
            throw ValidationException::withMessages([
                'payment' => [$verify['message'] ?? 'تأیید پرداخت ناموفق بود.'],
            ]);
        }

        $payment->update([
            'status' => 'paid',
            'ref_id' => $verify['ref_id'] ?: Str::random(10),
            'paid_at' => now(),
        ]);

        $this->activateSubscription($payment);

        return ['message' => 'پرداخت با موفقیت انجام شد.', 'payment' => $payment, 'success' => true];
    }

    public function getCurrentSubscription(Office $office): ?Subscription
    {
        return Subscription::with('plan')
            ->where('office_id', $office->id)
            ->where('status', 'active')
            ->latest('starts_at')
            ->first();
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

        $payment->update(['status' => 'paid', 'paid_at' => now(), 'authority' => 'wallet-'.$payment->id]);
        $this->activateSubscription($payment);

        return ['message' => 'اشتراک با موفقیت فعال شد.', 'payment' => $payment];
    }

    private function initiateZibal(Payment $payment, SubscriptionPlan $plan): array
    {
        $appUrl = rtrim(config('app.url'), '/');
        $callbackUrl = $appUrl.'/api/v1/payments/zibal/callback';

        $result = $this->zibal->request(
            (int) $payment->amount,
            "خرید اشتراک {$plan->name} — پوشه",
            $callbackUrl,
            $payment->id,
        );

        $payment->update(['authority' => $result['track_id']]);

        return [
            'payment_id' => $payment->id,
            'gateway' => 'zibal',
            'amount' => $payment->amount,
            'redirect_url' => $result['redirect_url'],
            'track_id' => $result['track_id'],
        ];
    }

    private function activateSubscription(Payment $payment): Subscription
    {
        $planId = $payment->metadata['plan_id'] ?? null;
        $plan = $planId
            ? SubscriptionPlan::findOrFail($planId)
            : SubscriptionPlan::where('is_active', true)->orderBy('sort_order')->firstOrFail();

        Subscription::where('office_id', $payment->office_id)
            ->where('status', 'active')
            ->update(['status' => 'expired']);

        $subscription = Subscription::create([
            'office_id' => $payment->office_id,
            'subscription_plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
            'auto_renew' => true,
        ]);

        Office::where('id', $payment->office_id)->update([
            'subscription_plan_id' => $plan->id,
            'panel_type' => $plan->panel_type,
        ]);

        return $subscription;
    }
}
