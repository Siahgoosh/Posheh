<?php

namespace App\Enums;

enum SubscriptionPlan: string
{
    case Basic = 'basic';
    case Professional = 'professional';
    case Unlimited = 'unlimited';

    public function label(): string
    {
        return match ($this) {
            self::Basic => 'پایه',
            self::Professional => 'حرفه‌ای',
            self::Unlimited => 'نامحدود',
        };
    }

    public function maxUsers(): int
    {
        return match ($this) {
            self::Basic => 3,
            self::Professional => 10,
            self::Unlimited => PHP_INT_MAX,
        };
    }

    public function maxProperties(): int
    {
        return match ($this) {
            self::Basic => 100,
            self::Professional => 1000,
            self::Unlimited => PHP_INT_MAX,
        };
    }

    public function storageGb(): int
    {
        return match ($this) {
            self::Basic => 5,
            self::Professional => 25,
            self::Unlimited => 100,
        };
    }

    public function monthlyPrice(): int
    {
        return match ($this) {
            self::Basic => 990_000,
            self::Professional => 2_490_000,
            self::Unlimited => 4_990_000,
        };
    }
}
