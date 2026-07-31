<?php

namespace App\Services\Admin;

use App\Models\Office;
use App\Models\OfficeHealthScore;

class OfficeHealthScoreService
{
    /** @return array{total: int, factors: array<string, int>} */
    public function calculate(Office $office): array
    {
        $office->loadCount(['properties', 'users']);
        $office->load(['subscription']);

        $factors = [
            'active' => $office->is_active ? 25 : 0,
            'subscription' => $office->subscription?->status === 'active' ? 25 : 5,
            'properties' => min(25, ($office->properties_count ?? 0) * 2),
            'users' => min(25, ($office->users_count ?? 0) * 5),
        ];

        return [
            'total' => (int) array_sum($factors),
            'factors' => $factors,
        ];
    }

    public function sync(Office $office): OfficeHealthScore
    {
        $score = $this->calculate($office);

        return OfficeHealthScore::updateOrCreate(
            ['office_id' => $office->id],
            [
                'score' => $score['total'],
                'factors' => $score['factors'],
                'calculated_at' => now(),
            ]
        );
    }

    /** @return list<array<string, mixed>> */
    public function allOffices(): array
    {
        return Office::with(['healthScore', 'subscription.plan'])
            ->withCount(['properties', 'users'])
            ->orderBy('name')
            ->get()
            ->map(function (Office $office) {
                $score = $this->calculate($office);
                $this->sync($office);

                return [
                    'id' => $office->id,
                    'name' => $office->name,
                    'slug' => $office->slug,
                    'is_active' => $office->is_active,
                    'properties_count' => $office->properties_count,
                    'users_count' => $office->users_count,
                    'health_score' => $score['total'],
                    'factors' => $score['factors'],
                    'plan' => $office->subscription?->plan?->name,
                    'subscription_status' => $office->subscription?->status,
                ];
            })
            ->sortByDesc('health_score')
            ->values()
            ->all();
    }
}
