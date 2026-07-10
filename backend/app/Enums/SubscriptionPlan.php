<?php

namespace App\Enums;

enum SubscriptionPlan: string
{
    case Solo = 'solo';
    case Office = 'office';
    case Premium = 'premium';

    /** @deprecated use Solo */
    case Basic = 'basic';
    /** @deprecated use Office */
    case Professional = 'professional';
    /** @deprecated use Premium */
    case Unlimited = 'unlimited';

    public function label(): string
    {
        return match ($this) {
            self::Solo => 'مشاور مستقل',
            self::Office => 'دفتر املاک',
            self::Premium => 'دفتر حرفه‌ای',
            self::Basic => 'پایه',
            self::Professional => 'حرفه‌ای',
            self::Unlimited => 'نامحدود',
        };
    }

    public function panelType(): PanelType
    {
        return match ($this) {
            self::Solo => PanelType::Solo,
            self::Office => PanelType::Office,
            self::Premium => PanelType::Premium,
            self::Basic => PanelType::Office,
            self::Professional => PanelType::Office,
            self::Unlimited => PanelType::Premium,
        };
    }
}
