<?php

namespace App\Modules\VirtualTour\Application\Services;

use App\Models\Office;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

final class TourUserScope
{
    public static function isPlatform(User $user): bool
    {
        return $user->isSuperAdmin() || $user->role->isPlatformStaff();
    }

    public static function officeId(User $user, ?int $requestedOfficeId = null): ?int
    {
        if ($user->office_id) {
            return $user->office_id;
        }

        if (! self::isPlatform($user)) {
            return null;
        }

        if ($requestedOfficeId) {
            return $requestedOfficeId;
        }

        return Office::where('slug', 'demo-office')->value('id')
            ?? Office::query()->orderBy('id')->value('id');
    }

    public static function applyOfficeScope(Builder $query, User $user): Builder
    {
        if (self::isPlatform($user) && ! $user->office_id) {
            return $query;
        }

        return $query->where('office_id', $user->office_id);
    }
}
