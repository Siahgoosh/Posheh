<?php

namespace App\Services\Subscription;

use App\Enums\PaymentGateway;
use App\Models\DiscountCode;
use App\Models\Office;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\Wallet;
use App\Services\Payment\DiscountCodeService;
use App\Services\Payment\ZibalService;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SubscriptionService
{
    public function __construct(
        private readonly ZibalService $zibal,
        private readonly DiscountCodeService $discountCodes,
    ) {}

    public function getPlans()
    {
        return SubscriptionPlan::where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }

    public function subscribe(Office $office, User $user, int $planId, PaymentGateway $gateway, ?string $discountCode = null): array
    {
        $plan = SubscriptionPlan::findOrFail($planId);
        $originalAmount = (int) $plan->monthly_price;
        $discountAmount = 0;
        $discountModel = null;
        $finalAmount = $originalAmount;

        if ($discountCode) {
            $discountModel = $this->discountCodes->findValid($discountCode, $plan);
            $discountAmount = $discountModel->calculateDiscount($originalAmount);
            $finalAmount = $discountModel->applyTo($originalAmount);
        }

        $payment = Payment::create([
            'office_id' => $office->id,
            'user_id' => $user->id,
            'user_phone' => $user->mobile,
            'discount_code_id' => $discountModel?->id,
            'gateway' => $gateway,
            'status' => 'pending',
            'amount' => $finalAmount,
            'original_amount' => $originalAmount,
            'discount_amount' => $discountAmount,
            'authority' => 'pending',
            'metadata' => [
                'plan_id' => $plan->id,
                'plan_name' => $plan->name,
                'user_name' => $user->name,
                'discount_code' => $discountModel?->code,
            ],
        ]);

        if ($gateway === PaymentGateway::Wallet) {
            return $this->payWithWallet($office, $plan, $payment, $discountModel);
        }

        if ($gateway === PaymentGateway::Zibal) {
            if ($finalAmount <= 0) {
                return $this->completeFreePayment($office, $plan, $payment, $discountModel);
            }

            return $this->initiateZibal($payment, $plan, $user->mobile);
        }

        return [
            'payment_id' => $payment->id,
            'gateway' => $gateway->value,
            'amount' => $payment->amount,
            'message' => 'پرداخت درگاه کافه‌بازار آماده است.',
        ];
    }

    public function previewDiscount(string $code, int $planId): array
    {
        $plan = SubscriptionPlan::findOrFail($planId);

        return $this->discountCodes->preview($code, $plan);
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

    private function payWithWallet(Office $office, SubscriptionPlan $plan, Payment $payment, ?DiscountCode $discount): array
    {
        $wallet = $office->wallet ?? Wallet::create(['office_id' => $office->id, 'balance' => 0]);

        if ($wallet->balance < $payment->amount) {
            throw ValidationException::withMessages([
                'wallet' => ['موجودی کیف پول کافی نیست.'],
            ]);
        }

        $wallet->decrement('balance', $payment->amount);
        $wallet->transactions()->create([
            'type' => 'debit',
            'amount' => $payment->amount,
            'balance_after' => $wallet->balance,
            'description' => "خرید اشتراک {$plan->name}",
            'reference_type' => Payment::class,
            'reference_id' => $payment->id,
        ]);

        $payment->update(['status' => 'paid', 'paid_at' => now(), 'authority' => 'wallet-'.$payment->id]);
        $this->activateSubscription($payment, $discount);

        return ['message' => 'اشتراک با موفقیت فعال شد.', 'payment' => $payment];
    }

    private function completeFreePayment(Office $office, SubscriptionPlan $plan, Payment $payment, ?DiscountCode $discount): array
    {
        $payment->update([
            'status' => 'paid',
            'paid_at' => now(),
            'authority' => 'discount-'.$payment->id,
        ]);
        $this->activateSubscription($payment, $discount);

        return [
            'message' => 'اشتراک با کد تخفیف فعال شد.',
            'payment' => $payment,
            'amount' => 0,
        ];
    }

    private function initiateZibal(Payment $payment, SubscriptionPlan $plan, ?string $mobile = null): array
    {
        $appUrl = rtrim(config('app.url'), '/');
        $callbackUrl = $appUrl.'/api/v1/payments/zibal/callback';

        $result = $this->zibal->request(
            (int) $payment->amount,
            "خرید اشتراک {$plan->name} — پوشه",
            $callbackUrl,
            $payment->id,
            $mobile,
        );

        $payment->update(['authority' => $result['track_id']]);

        return [
            'payment_id' => $payment->id,
            'gateway' => 'zibal',
            'amount' => $payment->amount,
            'original_amount' => $payment->original_amount,
            'discount_amount' => $payment->discount_amount,
            'redirect_url' => $result['redirect_url'],
            'track_id' => $result['track_id'],
        ];
    }

    private function activateSubscription(Payment $payment, ?DiscountCode $discount = null): Subscription
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
            'plan_active' => true,
            'trial_ends_at' => null,
        ]);

        $payment->update(['subscription_id' => $subscription->id]);

        if ($discount || $payment->discount_code_id) {
            $code = $discount ?? DiscountCode::find($payment->discount_code_id);
            if ($code) {
                $this->discountCodes->markUsed($code);
            }
        }

        return $subscription;
    }
}
