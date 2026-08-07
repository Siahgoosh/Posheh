<?php

namespace App\Modules\VirtualTour\Application\Services;

use App\Models\VirtualTour;
use App\Models\VirtualTourAnalyticsEvent;
use App\Models\VirtualTourLead;
use App\Models\VirtualTourView;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TourAnalyticsService
{
    /** @var list<string> */
    public const EVENT_TYPES = [
        'session_start',
        'session_end',
        'scene_view',
        'scene_complete',
        'hotspot_click',
        'zoom',
        'tour_complete',
    ];

    public function recordView(
        VirtualTour $tour,
        ?string $ip,
        ?string $ua,
        ?string $referrer,
        ?string $sessionId = null,
        ?array $device = null,
    ): void {
        VirtualTourView::create([
            'virtual_tour_id' => $tour->id,
            'session_id' => $sessionId,
            'ip' => $ip,
            'user_agent' => $ua ? Str::limit($ua, 500) : null,
            'referrer' => $referrer ? Str::limit($referrer, 500) : null,
            'device_type' => $device['device_type'] ?? null,
            'screen_width' => $device['screen_width'] ?? null,
            'screen_height' => $device['screen_height'] ?? null,
            'viewed_at' => now(),
        ]);
        $tour->increment('view_count');
    }

    public function recordEvents(VirtualTour $tour, string $sessionId, array $events): int
    {
        $rows = [];
        foreach ($events as $e) {
            $type = $e['event_type'] ?? null;
            if (! $type || ! in_array($type, self::EVENT_TYPES, true)) {
                continue;
            }
            $rows[] = [
                'virtual_tour_id' => $tour->id,
                'session_id' => Str::limit($sessionId, 64),
                'scene_id' => $e['scene_id'] ?? null,
                'hotspot_id' => $e['hotspot_id'] ?? null,
                'event_type' => $type,
                'position_x' => $e['position_x'] ?? null,
                'position_y' => $e['position_y'] ?? null,
                'meta' => isset($e['meta']) ? json_encode($e['meta']) : null,
                'created_at' => now(),
            ];
        }

        if (empty($rows)) {
            return 0;
        }

        VirtualTourAnalyticsEvent::insert($rows);

        return count($rows);
    }

    public function updateSessionDuration(VirtualTour $tour, string $sessionId, int $seconds): void
    {
        VirtualTourView::where('virtual_tour_id', $tour->id)
            ->where('session_id', $sessionId)
            ->latest('viewed_at')
            ->limit(1)
            ->update(['duration_seconds' => $seconds]);
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
        $query = VirtualTour::query();
        TourUserScope::applyOfficeScope($query, $user);
        $tour = $query->findOrFail($tourId);

        return array_merge($this->getBasicStats($tour), $this->getEngagementStats($tour));
    }

    public function getBasicStats(VirtualTour $tour): array
    {
        return [
            'view_count' => $tour->view_count,
            'leads_count' => $tour->leads()->count(),
            'recent_views' => $tour->views()->latest('viewed_at')->limit(20)->get(),
            'recent_leads' => $tour->leads()->latest()->limit(10)->get(),
        ];
    }

    public function getEngagementStats(VirtualTour $tour): array
    {
        $since = now()->subDays(90);

        $sceneViews = VirtualTourAnalyticsEvent::query()
            ->where('virtual_tour_id', $tour->id)
            ->where('event_type', 'scene_view')
            ->where('created_at', '>=', $since)
            ->select('scene_id', DB::raw('COUNT(*) as views'))
            ->groupBy('scene_id')
            ->orderByDesc('views')
            ->get();

        $hotspotClicks = VirtualTourAnalyticsEvent::query()
            ->where('virtual_tour_id', $tour->id)
            ->where('event_type', 'hotspot_click')
            ->where('created_at', '>=', $since)
            ->select('hotspot_id', DB::raw('COUNT(*) as clicks'))
            ->groupBy('hotspot_id')
            ->orderByDesc('clicks')
            ->limit(20)
            ->get();

        $sessions = VirtualTourAnalyticsEvent::query()
            ->where('virtual_tour_id', $tour->id)
            ->where('created_at', '>=', $since)
            ->distinct('session_id')
            ->count('session_id');

        $completions = VirtualTourAnalyticsEvent::query()
            ->where('virtual_tour_id', $tour->id)
            ->where('event_type', 'tour_complete')
            ->where('created_at', '>=', $since)
            ->count();

        $avgDuration = VirtualTourView::query()
            ->where('virtual_tour_id', $tour->id)
            ->whereNotNull('duration_seconds')
            ->where('viewed_at', '>=', $since)
            ->avg('duration_seconds');

        $deviceBreakdown = VirtualTourView::query()
            ->where('virtual_tour_id', $tour->id)
            ->where('viewed_at', '>=', $since)
            ->select('device_type', DB::raw('COUNT(*) as count'))
            ->groupBy('device_type')
            ->get();

        $heatmap = VirtualTourAnalyticsEvent::query()
            ->where('virtual_tour_id', $tour->id)
            ->where('event_type', 'hotspot_click')
            ->whereNotNull('position_x')
            ->whereNotNull('position_y')
            ->where('created_at', '>=', $since)
            ->select('scene_id', 'position_x', 'position_y', DB::raw('COUNT(*) as weight'))
            ->groupBy('scene_id', 'position_x', 'position_y')
            ->limit(500)
            ->get();

        $mostViewedScene = $sceneViews->first();

        return [
            'engagement' => [
                'unique_sessions' => $sessions,
                'tour_completions' => $completions,
                'completion_rate' => $sessions > 0 ? round($completions / $sessions * 100, 1) : 0,
                'avg_viewing_seconds' => round((float) $avgDuration, 1),
                'most_viewed_scene_id' => $mostViewedScene?->scene_id,
                'most_viewed_scene_views' => $mostViewedScene?->views ?? 0,
            ],
            'scene_views' => $sceneViews,
            'hotspot_clicks' => $hotspotClicks,
            'device_breakdown' => $deviceBreakdown,
            'heatmap_points' => $heatmap,
        ];
    }
}
