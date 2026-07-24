<?php

namespace App\Enums;

enum UserRole: string
{
    case SuperAdmin = 'super_admin';
    case PlatformAdmin = 'platform_admin';
    case PlatformSupport = 'platform_support';
    case PlatformFinance = 'platform_finance';
    case OfficeManager = 'office_manager';
    case Consultant = 'consultant';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'مدیر سیستم',
            self::PlatformAdmin => 'مدیر پلتفرم',
            self::PlatformSupport => 'پشتیبانی',
            self::PlatformFinance => 'مالی',
            self::OfficeManager => 'مدیر دفتر',
            self::Consultant => 'مشاور',
        };
    }

    /** @return list<string> */
    public static function platformRoles(): array
    {
        return [
            self::SuperAdmin->value,
            self::PlatformAdmin->value,
            self::PlatformSupport->value,
            self::PlatformFinance->value,
        ];
    }

    public function isPlatformStaff(): bool
    {
        return in_array($this->value, self::platformRoles(), true);
    }
}
