<?php

namespace App\Enums;

enum PropertyPermission: string
{
    case Private = 'private';
    case Team = 'team';
    case Office = 'office';
    case ManagerOnly = 'manager_only';

    public function label(): string
    {
        return match ($this) {
            self::Private => 'خصوصی',
            self::Team => 'تیمی',
            self::Office => 'دفتری',
            self::ManagerOnly => 'فقط مدیر',
        };
    }
}
