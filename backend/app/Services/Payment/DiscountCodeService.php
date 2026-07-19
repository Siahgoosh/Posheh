<?php

namespace App\Services\Payment;

use App\Models\DiscountCode;
use App\Models\SubscriptionPlan;
use Illuminate\Validation\ValidationException;

class DiscountCodeService
{
    public function findValid(string $code, SubscriptionPlan $plan): DiscountCode
    {
        $discount = DiscountCode::whereRaw('UPPER(code) = ?', [strtoupper(trim($code))])->first();

        if (! $discount) {
            throw ValidationException::withMessages([
                'discount_code' => ['کد تخفیف معتبر نیست.'],
            ]);
        }

        if (! $discount->is_active) {
            throw ValidationException::withMessages([
                'discount_code' => ['این کد تخفیف غیرفعال است.'],
            ]);
        }

        if ($discount->valid_from && $discount->valid_from->isFuture()) {
            throw ValidationException::withMessages([
                'discount_code' => ['این کد تخفیف هنوز فعال نشده است.'],
            ]);
        }

        if ($discount->valid_until && $discount->valid_until->isPast()) {
            throw ValidationException::withMessages([
                'discount_code' => ['مهلت استفاده از این کد تخفیف به پایان رسیده است.'],
            ]);
        }

        if ($discount->max_uses !== null && $discount->used_count >= $discount->max_uses) {
            throw ValidationException::withMessages([
                'discount_code' => ['سقف استفاده از این کد تخفیف پر شده است.'],
            ]);
        }

        if ($discount->subscription_plan_id && $discount->subscription_plan_id !== $plan->id) {
            throw ValidationException::withMessages([
                'discount_code' => ['این کد تخفیف برای پلن انتخاب‌شده معتبر نیست.'],
            ]);
        }

        return $discount;
    }

    public function preview(string $code, SubscriptionPlan $plan): array
    {
        $discount = $this->findValid($code, $plan);
        $original = (int) $plan->monthly_price;
        $discountAmount = $discount->calculateDiscount($original);
        $final = $discount->applyTo($original);

        return [
            'code' => $discount->code,
            'type' => $discount->type,
            'value' => $discount->value,
            'original_amount' => $original,
            'discount_amount' => $discountAmount,
            'final_amount' => $final,
        ];
    }

    public function markUsed(DiscountCode $discount): void
    {
        $discount->increment('used_count');
    }
}
