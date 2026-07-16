<?php

namespace App\Enums;

enum PropertyCategory: string
{
    case Land = 'land';
    case Apartment = 'apartment';
    case Villa = 'villa';
    case OldHouse = 'old_house';
    case Shop = 'shop';
    case Office = 'office';
    case CommercialUnit = 'commercial_unit';
    case Warehouse = 'warehouse';
    case Factory = 'factory';
    case Garden = 'garden';
    case GardenVilla = 'garden_villa';
    case AgriculturalLand = 'agricultural_land';
    case Greenhouse = 'greenhouse';
    case Livestock = 'livestock';
    case Storage = 'storage';
    case ConstructionProject = 'construction_project';
    case PreSaleUnit = 'pre_sale_unit';
    case ResidentialComplex = 'residential_complex';
    case CommercialComplex = 'commercial_complex';
    case Hotel = 'hotel';
    case Parking = 'parking';
    case Suite = 'suite';
    case Townhouse = 'townhouse';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Land => 'زمین',
            self::Apartment => 'آپارتمان',
            self::Villa => 'ویلا',
            self::OldHouse => 'خانه کلنگی',
            self::Shop => 'مغازه',
            self::Office => 'دفتر اداری',
            self::CommercialUnit => 'واحد تجاری',
            self::Warehouse => 'سوله',
            self::Factory => 'کارخانه',
            self::Garden => 'باغ',
            self::GardenVilla => 'باغ ویلا',
            self::AgriculturalLand => 'زمین کشاورزی',
            self::Greenhouse => 'گلخانه',
            self::Livestock => 'دامداری',
            self::Storage => 'انبار',
            self::ConstructionProject => 'پروژه مشارکتی',
            self::PreSaleUnit => 'پیش‌فروش',
            self::ResidentialComplex => 'مجتمع مسکونی',
            self::CommercialComplex => 'مجتمع تجاری',
            self::Hotel => 'هتل',
            self::Parking => 'پارکینگ',
            self::Suite => 'سوئیت',
            self::Townhouse => 'تاون‌هاوس',
            self::Other => 'سایر',
        };
    }
}
