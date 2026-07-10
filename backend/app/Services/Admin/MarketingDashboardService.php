<?php

namespace App\Services\Admin;

use App\Models\AnalyticsEvent;
use App\Models\AppRelease;
use App\Models\BlogPost;
use App\Models\Office;
use App\Models\OtpCode;
use App\Models\Payment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class MarketingDashboardService
{
    /** @return array<string, mixed> */
    public function snapshot(): array
    {
        $now = now();
        $today = $now->copy()->startOfDay();
        $weekStart = $now->copy()->subDays(6)->startOfDay();
        $monthStart = $now->copy()->subDays(29)->startOfDay();

        return [
            'generated_at' => $now->toIso8601String(),
            'users' => $this->userStats($today, $weekStart),
            'offices' => $this->officeStats(),
            'blog' => $this->blogStats(),
            'traffic' => $this->trafficStats($today, $weekStart, $monthStart),
            'downloads' => $this->downloadStats($today, $weekStart),
            'revenue' => $this->revenueStats(),
            'auth' => $this->authStats($today, $weekStart),
            'top_pages' => $this->topPages($monthStart),
            'top_referrers' => $this->topReferrers($monthStart),
            'visits_chart' => $this->visitsChart($monthStart),
            'registrations_chart' => $this->registrationsChart($monthStart),
        ];
    }

    /** @return array<string, int|float> */
    private function userStats(Carbon $today, Carbon $weekStart): array
    {
        return [
            'total' => User::count(),
            'active' => User::where('is_active', true)->count(),
            'new_today' => User::where('created_at', '>=', $today)->count(),
            'new_week' => User::where('created_at', '>=', $weekStart)->count(),
            'super_admins' => User::where('role', 'super_admin')->count(),
            'managers' => User::where('role', 'office_manager')->count(),
            'consultants' => User::where('role', 'consultant')->count(),
        ];
    }

    /** @return array<string, int> */
    private function officeStats(): array
    {
        return [
            'total' => Office::count(),
            'active' => Office::where('is_active', true)->count(),
            'on_trial' => Office::whereNotNull('trial_ends_at')->where('trial_ends_at', '>', now())->count(),
        ];
    }

    /** @return array<string, int|list<array<string, mixed>> */
    private function blogStats(): array
    {
        return [
            'total_posts' => BlogPost::count(),
            'published_posts' => BlogPost::where('is_published', true)->count(),
            'total_views' => (int) BlogPost::sum('views'),
            'top_posts' => BlogPost::orderByDesc('views')->limit(5)->get(['id', 'title', 'slug', 'views', 'is_published'])->map(fn ($p) => [
                'id' => $p->id,
                'title' => $p->title,
                'slug' => $p->slug,
                'views' => $p->views,
                'is_published' => $p->is_published,
            ])->all(),
        ];
    }

    /** @return array<string, int> */
    private function trafficStats(Carbon $today, Carbon $weekStart, Carbon $monthStart): array
    {
        $base = AnalyticsEvent::where('event_type', 'page_view');

        return [
            'views_today' => (clone $base)->where('created_at', '>=', $today)->count(),
            'views_week' => (clone $base)->where('created_at', '>=', $weekStart)->count(),
            'views_month' => (clone $base)->where('created_at', '>=', $monthStart)->count(),
            'unique_today' => (clone $base)->where('created_at', '>=', $today)->distinct('visitor_hash')->count('visitor_hash'),
            'unique_week' => (clone $base)->where('created_at', '>=', $weekStart)->distinct('visitor_hash')->count('visitor_hash'),
            'unique_month' => (clone $base)->where('created_at', '>=', $monthStart)->distinct('visitor_hash')->count('visitor_hash'),
        ];
    }

    /** @return array<string, int|list<array<string, mixed>>> */
    private function downloadStats(Carbon $today, Carbon $weekStart): array
    {
        $base = AnalyticsEvent::where('event_type', 'download_click');

        $byPlatform = (clone $base)
            ->where('created_at', '>=', $weekStart)
            ->get()
            ->groupBy(fn ($event) => (string) ($event->meta['platform'] ?? 'unknown'))
            ->map(fn ($items) => $items->count())
            ->all();

        return [
            'clicks_today' => (clone $base)->where('created_at', '>=', $today)->count(),
            'clicks_week' => (clone $base)->where('created_at', '>=', $weekStart)->count(),
            'by_platform_week' => $byPlatform,
            'published_releases' => AppRelease::published()->count(),
        ];
    }

    /** @return array<string, int|float> */
    private function revenueStats(): array
    {
        return [
            'total' => (int) Payment::where('status', 'paid')->sum('amount'),
            'monthly' => (int) Payment::where('status', 'paid')->whereMonth('paid_at', now()->month)->sum('amount'),
            'paid_count' => Payment::where('status', 'paid')->count(),
        ];
    }

    /** @return array<string, int> */
    private function authStats(Carbon $today, Carbon $weekStart): array
    {
        return [
            'otp_sent_today' => OtpCode::where('created_at', '>=', $today)->count(),
            'otp_sent_week' => OtpCode::where('created_at', '>=', $weekStart)->count(),
            'logins_today' => User::where('last_login_at', '>=', $today)->count(),
            'logins_week' => User::where('last_login_at', '>=', $weekStart)->count(),
        ];
    }

    /** @return list<array{path: string, views: int}> */
    private function topPages(Carbon $since): array
    {
        return AnalyticsEvent::query()
            ->where('event_type', 'page_view')
            ->where('created_at', '>=', $since)
            ->select('path', DB::raw('count(*) as views'))
            ->groupBy('path')
            ->orderByDesc('views')
            ->limit(10)
            ->get()
            ->map(fn ($row) => ['path' => (string) $row->path, 'views' => (int) $row->views])
            ->all();
    }

    /** @return list<array{referrer: string, views: int}> */
    private function topReferrers(Carbon $since): array
    {
        return AnalyticsEvent::query()
            ->where('event_type', 'page_view')
            ->where('created_at', '>=', $since)
            ->whereNotNull('referrer')
            ->where('referrer', '!=', '')
            ->select('referrer', DB::raw('count(*) as views'))
            ->groupBy('referrer')
            ->orderByDesc('views')
            ->limit(8)
            ->get()
            ->map(fn ($row) => ['referrer' => (string) $row->referrer, 'views' => (int) $row->views])
            ->all();
    }

    /** @return list<array{date: string, views: int, unique: int}> */
    private function visitsChart(Carbon $since): array
    {
        $rows = AnalyticsEvent::query()
            ->where('event_type', 'page_view')
            ->where('created_at', '>=', $since)
            ->select(DB::raw('DATE(created_at) as day'), DB::raw('count(*) as views'), DB::raw('count(distinct visitor_hash) as unique_visitors'))
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        return $rows->map(fn ($row) => [
            'date' => (string) $row->day,
            'views' => (int) $row->views,
            'unique' => (int) $row->unique_visitors,
        ])->all();
    }

    /** @return list<array{date: string, count: int}> */
    private function registrationsChart(Carbon $since): array
    {
        return User::query()
            ->where('created_at', '>=', $since)
            ->select(DB::raw('DATE(created_at) as day'), DB::raw('count(*) as count'))
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->map(fn ($row) => ['date' => (string) $row->day, 'count' => (int) $row->count])
            ->all();
    }
}
