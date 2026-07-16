<?php

namespace App\Enums;

enum PropertyType: string
{
    case Sale = 'sale';
    case FullMortgage = 'full_mortgage';
    case Rent = 'rent';
    case MortgageRent = 'mortgage_rent';
    case PreSale = 'pre_sale';
    case ConstructionPartnership = 'construction_partnership';
    case Exchange = 'exchange';
    case Barter = 'barter';
    case InstallmentSale = 'installment_sale';
    case Auction = 'auction';

    /** @deprecated legacy values mapped on read */
    case Land = 'land';
    case Garden = 'garden';
    case Commercial = 'commercial';
    case Warehouse = 'warehouse';
    case Partnership = 'partnership';
    case Mortgage = 'mortgage';

    public function label(): string
    {
        return match ($this) {
            self::Sale => 'فروش',
            self::FullMortgage, self::Mortgage => 'رهن کامل',
            self::Rent => 'اجاره',
            self::MortgageRent => 'رهن و اجاره',
            self::PreSale => 'پیش‌فروش',
            self::ConstructionPartnership, self::Partnership => 'مشارکت در ساخت',
            self::Exchange => 'معاوضه',
            self::Barter => 'تهاتر',
            self::InstallmentSale => 'فروش اقساطی',
            self::Auction => 'مزایده',
            self::Land => 'فروش',
            self::Garden => 'فروش',
            self::Commercial => 'فروش',
            self::Warehouse => 'فروش',
        };
    }

    public function isLegacy(): bool
    {
        return in_array($this, [self::Land, self::Garden, self::Commercial, self::Warehouse, self::Partnership, self::Mortgage], true);
    }
}
