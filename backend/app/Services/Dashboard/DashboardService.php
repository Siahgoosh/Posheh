<?php

namespace App\Services\Dashboard;

use App\Enums\PropertyStatus;
use App\Models\Activity;
use App\Models\Property;
use App\Models\Task;
use App\Models\User;
use App\Repositories\Contracts\PropertyRepositoryInterface;

class DashboardService
{
    public function __construct(
        private readonly PropertyRepositoryInterface $propertyRepository,
    ) {}

    public function getStats(User $user): array
    {
        $officeId = $user->office_id;

        $propertyQuery = Property::query()->visibleTo($user);

        return [
            'total_properties' => (clone $propertyQuery)->count(),
            'active_properties' => (clone $propertyQuery)->where('status', PropertyStatus::Active)->count(),
            'expired_properties' => (clone $propertyQuery)->where('status', PropertyStatus::Expired)->count(),
            'today_properties' => (clone $propertyQuery)->whereDate('created_at', today())->count(),
            'expiring_soon' => (clone $propertyQuery)->expiringSoon(7)->count(),
            'team_members' => User::where('office_id', $officeId)->where('is_active', true)->count(),
            'pending_tasks' => Task::where('office_id', $officeId)
                ->where('assigned_to', $user->id)
                ->where('status', 'pending')
                ->count(),
        ];
    }

    public function getRecentProperties(User $user, int $limit = 10)
    {
        return Property::query()
            ->visibleTo($user)
            ->with(['media', 'creator'])
            ->latest()
            ->limit($limit)
            ->get();
    }

    public function getExpiringProperties(User $user, int $limit = 10)
    {
        return Property::query()
            ->visibleTo($user)
            ->expiringSoon(7)
            ->with(['media', 'creator'])
            ->orderBy('expires_at')
            ->limit($limit)
            ->get();
    }

    public function getRecentActivities(User $user, int $limit = 20)
    {
        return Activity::where('office_id', $user->office_id)
            ->with('user')
            ->latest()
            ->limit($limit)
            ->get();
    }

    public function getTasks(User $user, int $limit = 10)
    {
        return Task::where('office_id', $user->office_id)
            ->where(function ($q) use ($user) {
                $q->where('assigned_to', $user->id);
                if ($user->canManageOffice()) {
                    $q->orWhere('created_by', $user->id);
                }
            })
            ->with(['property', 'assignee'])
            ->orderByRaw("FIELD(status, 'pending', 'in_progress', 'completed')")
            ->orderBy('due_at')
            ->limit($limit)
            ->get();
    }
}
