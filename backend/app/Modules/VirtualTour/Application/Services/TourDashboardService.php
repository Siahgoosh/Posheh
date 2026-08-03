<?php

namespace App\Modules\VirtualTour\Application\Services;

use App\Models\User;
use App\Models\VirtualTour;

class TourDashboardService
{
    public function stats(User $user): array
    {
        $base = VirtualTour::where('office_id', $user->office_id);

        return [
            'total' => (clone $base)->count(),
            'published' => (clone $base)->where('status', 'published')->count(),
            'draft' => (clone $base)->where('status', 'draft')->count(),
            'archived' => (clone $base)->where('status', 'archived')->count(),
            'total_views' => (clone $base)->sum('view_count'),
            'total_leads' => (clone $base)->withCount('leads')->get()->sum('leads_count'),
            'recent' => (clone $base)
                ->with(['scenes:id,virtual_tour_id,name,thumbnail_path', 'property:id,code'])
                ->withCount('views', 'leads', 'scenes')
                ->latest()
                ->limit(5)
                ->get(),
        ];
    }

    public function list(User $user, ?string $status = null, ?string $search = null)
    {
        $query = VirtualTour::where('office_id', $user->office_id)
            ->with(['property:id,code,city', 'scenes:id,virtual_tour_id,name,thumbnail_path,status'])
            ->withCount('views', 'leads', 'scenes');

        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        if ($search) {
            $query->where('title', 'like', '%'.addcslashes($search, '%_').'%');
        }

        return $query->latest()->paginate(20);
    }
}
