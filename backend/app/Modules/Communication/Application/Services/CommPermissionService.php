<?php

namespace App\Modules\Communication\Application\Services;

use Illuminate\Support\Facades\DB;

class CommPermissionService
{
    public function userCan(string $role, string $permissionSlug): bool
    {
        return DB::table('comm_role_permissions')
            ->join('comm_permissions', 'comm_permissions.id', '=', 'comm_role_permissions.permission_id')
            ->where('comm_role_permissions.role', $role)
            ->where('comm_permissions.slug', $permissionSlug)
            ->exists();
    }

    /** @return list<string> */
    public function permissionsForRole(string $role): array
    {
        return DB::table('comm_role_permissions')
            ->join('comm_permissions', 'comm_permissions.id', '=', 'comm_role_permissions.permission_id')
            ->where('comm_role_permissions.role', $role)
            ->pluck('comm_permissions.slug')
            ->all();
    }
}
