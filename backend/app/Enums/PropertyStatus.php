<?php

namespace App\Enums;

enum PropertyStatus: string
{
    case Active = 'active';
    case Reserved = 'reserved';
    case Sold = 'sold';
    case Rented = 'rented';
    case Archived = 'archived';
    case Cancelled = 'cancelled';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'فعال',
            self::Reserved => 'رزرو',
            self::Sold => 'فروخته شده',
            self::Rented => 'اجاره رفته',
            self::Archived => 'آرشیو',
            self::Cancelled => 'باطل',
            self::Expired => 'منقضی',
        };
    }
}
