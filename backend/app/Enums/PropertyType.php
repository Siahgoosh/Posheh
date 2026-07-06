<?php

namespace App\Enums;

enum PropertyType: string
{
    case Sale = 'sale';
    case Rent = 'rent';
    case Mortgage = 'mortgage';
    case PreSale = 'pre_sale';
    case Land = 'land';
    case Garden = 'garden';
    case Commercial = 'commercial';
    case Warehouse = 'warehouse';
    case Partnership = 'partnership';

    public function label(): string
    {
        return match ($this) {
            self::Sale => 'فروش',
            self::Rent => 'اجاره',
            self::Mortgage => 'رهن',
            self::PreSale => 'پیش‌فروش',
            self::Land => 'زمین',
            self::Garden => 'باغ',
            self::Commercial => 'تجاری',
            self::Warehouse => 'انبار',
            self::Partnership => 'مشارکت',
        };
    }
}
