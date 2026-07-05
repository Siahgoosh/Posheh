<?php

namespace App\Enums;

enum PaymentGateway: string
{
    case ZarinPal = 'zarinpal';
    case Aqayepardakht = 'aqayepardakht';
    case CafeBazaar = 'cafe_bazaar';
    case Wallet = 'wallet';

    public function label(): string
    {
        return match ($this) {
            self::ZarinPal => 'زرین‌پال',
            self::Aqayepardakht => 'آقای پرداخت',
            self::CafeBazaar => 'کافه‌بازار',
            self::Wallet => 'کیف پول',
        };
    }
}
