<?php

namespace App\Services\Admin;

use App\Models\AnalyticsEvent;
use App\Models\Device;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class PlatformUsersReportService
{
    private const ACTIVE_DAYS = 30;

    /** @return array<string, mixed> */
    public function summary(): array
    {
        $users = $this->enrichedUsers();
        $activeCutoff = now()->subDays(self::ACTIVE_DAYS);

        $byPlatform = ['android' => 0, 'windows' => 0, 'pwa' => 0, 'web' => 0, 'unknown' => 0];
        $activeByPlatform = ['android' => 0, 'windows' => 0, 'pwa' => 0, 'web' => 0, 'unknown' => 0];
        $inactiveByPlatform = ['android' => 0, 'windows' => 0, 'pwa' => 0, 'web' => 0, 'unknown' => 0];

        foreach ($users as $row) {
            $platform = $row['platform'];
            $byPlatform[$platform] = ($byPlatform[$platform] ?? 0) + 1;
            if ($row['is_active']) {
                $activeByPlatform[$platform] = ($activeByPlatform[$platform] ?? 0) + 1;
            } else {
                $inactiveByPlatform[$platform] = ($inactiveByPlatform[$platform] ?? 0) + 1;
            }
        }

        $traffic = AnalyticsEvent::where('event_type', 'page_view');
        $today = now()->startOfDay();
        $month = now()->subDays(29)->startOfDay();

        return [
            'generated_at' => now()->toIso8601String(),
            'active_days_threshold' => self::ACTIVE_DAYS,
            'traffic' => [
                'views_today' => (clone $traffic)->where('created_at', '>=', $today)->count(),
                'views_month' => (clone $traffic)->where('created_at', '>=', $month)->count(),
                'unique_month' => (clone $traffic)->where('created_at', '>=', $month)->distinct('visitor_hash')->count('visitor_hash'),
            ],
            'users' => [
                'total' => $users->count(),
                'active' => $users->where('is_active', true)->count(),
                'inactive' => $users->where('is_active', false)->count(),
                'by_platform' => $byPlatform,
                'active_by_platform' => $activeByPlatform,
                'inactive_by_platform' => $inactiveByPlatform,
            ],
            'rows' => $users->values()->all(),
        ];
    }

    /** @return Collection<int, array<string, mixed>> */
    public function enrichedUsers(): Collection
    {
        $devices = Device::query()
            ->orderByDesc('last_active_at')
            ->get()
            ->unique('user_id')
            ->keyBy('user_id');

        return User::with('office:id,name')
            ->orderBy('name')
            ->get()
            ->map(function (User $user) use ($devices) {
                $device = $devices->get($user->id);
                $platform = $this->normalizePlatform($device?->platform);
                $lastActivity = $this->latestActivity($user, $device);

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'mobile' => $user->mobile,
                    'email' => $user->email,
                    'username' => $user->username,
                    'role' => $user->role?->value,
                    'role_label' => $user->role?->label(),
                    'office_name' => $user->office?->name,
                    'platform' => $platform,
                    'platform_label' => $this->platformLabel($platform),
                    'app_version' => $device?->app_version,
                    'device_name' => $device?->device_name,
                    'last_active_at' => $lastActivity?->toIso8601String(),
                    'is_active' => $lastActivity && $lastActivity->gte(now()->subDays(self::ACTIVE_DAYS)),
                    'account_active' => (bool) $user->is_active,
                    'created_at' => $user->created_at?->toIso8601String(),
                ];
            });
    }

    private function normalizePlatform(?string $platform): string
    {
        $p = strtolower((string) $platform);

        return match (true) {
            in_array($p, ['android'], true) => 'android',
            in_array($p, ['windows', 'win32'], true) => 'windows',
            in_array($p, ['pwa', 'ios'], true) => 'pwa',
            in_array($p, ['web', ''], true) => 'web',
            default => 'unknown',
        };
    }

    private function platformLabel(string $platform): string
    {
        return match ($platform) {
            'android' => 'اندروید',
            'windows' => 'ویندوز',
            'pwa' => 'PWA / iPhone',
            'web' => 'وب',
            default => 'نامشخص',
        };
    }

    private function latestActivity(User $user, ?Device $device): ?Carbon
    {
        $times = array_filter([
            $device?->last_active_at,
            $user->last_login_at,
        ]);

        if ($times === []) {
            return null;
        }

        return collect($times)->sortDesc()->first();
    }
}
