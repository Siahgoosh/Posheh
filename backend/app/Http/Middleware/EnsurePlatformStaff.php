<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePlatformStaff
{
    public function handle(Request $request, Closure $next, ?string $permission = null): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if (! $user->canAccessAdminPanel()) {
            return response()->json(['message' => 'دسترسی به پنل مدیریت مجاز نیست.'], 403);
        }

        if ($permission && ! $this->hasPermission($user->role, $permission)) {
            return response()->json(['message' => 'شما مجاز به انجام این عملیات نیستید.'], 403);
        }

        return $next($request);
    }

    private function hasPermission(UserRole $role, string $permission): bool
    {
        if (in_array($role, [UserRole::SuperAdmin, UserRole::PlatformAdmin], true)) {
            return true;
        }

        $map = [
            UserRole::PlatformSupport->value => ['dashboard', 'tenants', 'users', 'tickets', 'announcements', 'search'],
            UserRole::PlatformFinance->value => ['dashboard', 'payments', 'subscriptions', 'wallets', 'coupons', 'reports'],
        ];

        return in_array($permission, $map[$role->value] ?? [], true);
    }
}
