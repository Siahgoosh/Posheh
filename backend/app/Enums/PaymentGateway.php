<?php

namespace App\Enums;

enum PaymentGateway: string
{
    case Zibal = 'zibal';
    case CafeBazaar = 'cafe_bazaar';
    case Wallet = 'wallet';

    public function label(): string
    {
        return match ($this) {
            self::Zibal => 'زیبال',
            self::CafeBazaar => 'کافه‌بازار',
            self::Wallet => 'کیف پول',
        };
    }
}
