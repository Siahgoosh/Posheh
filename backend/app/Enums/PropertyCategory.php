<?php

namespace App\Enums;

enum PropertyCategory: string
{
    case Apartment = 'apartment';
    case Villa = 'villa';
    case Land = 'land';
    case Shop = 'shop';
    case Office = 'office';
    case Warehouse = 'warehouse';
    case Suite = 'suite';
    case Townhouse = 'townhouse';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Apartment => 'آپارتمان',
            self::Villa => 'ویلا',
            self::Land => 'زمین / کلنگی',
            self::Shop => 'مغازه',
            self::Office => 'دفتر اداری',
            self::Warehouse => 'انبار',
            self::Suite => 'سوئیت',
            self::Townhouse => 'تاون‌هاوس',
            self::Other => 'سایر',
        };
    }
}
