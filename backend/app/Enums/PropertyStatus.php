<?php

namespace App\Enums;

enum PropertyStatus: string
{
    case Active = 'active';
    case Expired = 'expired';
    case Sold = 'sold';
    case Rented = 'rented';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'فعال',
            self::Expired => 'منقضی',
            self::Sold => 'فروخته شده',
            self::Rented => 'اجاره رفته',
            self::Archived => 'بایگانی',
        };
    }
}
