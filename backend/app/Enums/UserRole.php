<?php

namespace App\Enums;

enum UserRole: string
{
    case SuperAdmin = 'super_admin';
    case OfficeManager = 'office_manager';
    case Consultant = 'consultant';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'مدیر سیستم',
            self::OfficeManager => 'مدیر دفتر',
            self::Consultant => 'مشاور',
        };
    }
}
