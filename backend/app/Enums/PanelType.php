<?php

namespace App\Enums;

enum PanelType: string
{
    case Solo = 'solo';
    case Office = 'office';
    case Premium = 'premium';

    public function label(): string
    {
        return match ($this) {
            self::Solo => 'مشاور مستقل',
            self::Office => 'دفتر املاک (تا ۳ مشاور)',
            self::Premium => 'دفتر حرفه‌ای',
        };
    }

    public function isSolo(): bool
    {
        return $this === self::Solo;
    }

    public function requiresOfficeDetails(): bool
    {
        return $this !== self::Solo;
    }
}
