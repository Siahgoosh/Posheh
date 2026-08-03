<?php

namespace App\Modules\VirtualTour\Application\Services;

use App\Models\User;
use App\Models\VirtualTour;
use App\Models\VirtualTourLead;
use App\Models\VirtualTourView;
use Illuminate\Support\Str;

class TourAnalyticsService
{
    public function recordView(VirtualTour $tour, ?string $ip, ?string $ua, ?string $referrer): void
    {
        VirtualTourView::create([
            'virtual_tour_id' => $tour->id,
            'ip' => $ip,
            'user_agent' => $ua ? Str::limit($ua, 500) : null,
            'referrer' => $referrer ? Str::limit($referrer, 500) : null,
            'viewed_at' => now(),
        ]);
        $tour->increment('view_count');
    }

    public function submitLead(VirtualTour $tour, array $data): VirtualTourLead
    {
        return VirtualTourLead::create([
            'virtual_tour_id' => $tour->id,
            'name' => $data['name'],
            'mobile' => $data['mobile'],
            'message' => $data['message'] ?? null,
        ]);
    }

    public function getAnalytics(User $user, int $tourId): array
    {
        $tour = VirtualTour::where('office_id', $user->office_id)->findOrFail($tourId);

        return [
            'view_count' => $tour->view_count,
            'leads_count' => $tour->leads()->count(),
            'recent_views' => $tour->views()->latest('viewed_at')->limit(20)->get(),
            'recent_leads' => $tour->leads()->latest()->limit(10)->get(),
        ];
    }
}
