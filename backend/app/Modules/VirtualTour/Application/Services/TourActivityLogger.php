<?php

namespace App\Modules\VirtualTour\Application\Services;

use App\Models\User;
use App\Models\VirtualTour;
use App\Models\VirtualTourActivityLog;
use Illuminate\Http\Request;

class TourActivityLogger
{
    public function log(VirtualTour $tour, string $action, ?User $user = null, ?Request $request = null, ?array $meta = null): void
    {
        VirtualTourActivityLog::create([
            'virtual_tour_id' => $tour->id,
            'user_id' => $user?->id,
            'action' => $action,
            'ip' => $request?->ip(),
            'meta' => $meta,
            'created_at' => now(),
        ]);
    }

    public function recent(VirtualTour $tour, int $limit = 30)
    {
        return $tour->activityLogs()
            ->with('user:id,name')
            ->latest('created_at')
            ->limit($limit)
            ->get();
    }
}
