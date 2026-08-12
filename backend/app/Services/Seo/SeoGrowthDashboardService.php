<?php

namespace App\Services\Seo;

use App\Models\BlogGscMetric;
use App\Models\BlogPost;
use App\Models\Seo\SeoAlert;
use App\Models\Seo\SeoContentHealth;
use App\Models\Seo\SeoOpportunity;
use App\Models\Seo\SeoRecommendation;
use App\Models\Seo\SeoReportSnapshot;
use App\Models\Seo\SeoSearchQuery;
use App\Models\Seo\SeoTopicScore;
use Illuminate\Support\Facades\Schema;

class SeoGrowthDashboardService
{
    /** @return array<string, mixed> */
    public function executive(): array
    {
        $gscConfigured = (bool) config('blog.gsc.enabled') && (string) config('blog.gsc.credentials_json') !== '';
        $hasMetrics = Schema::hasTable('blog_gsc_metrics') && BlogGscMetric::query()->exists();

        $clicks = $hasMetrics ? (int) BlogGscMetric::query()->where('date', '>=', now()->subDays(28)->toDateString())->sum('clicks') : null;
        $impressions = $hasMetrics ? (int) BlogGscMetric::query()->where('date', '>=', now()->subDays(28)->toDateString())->sum('impressions') : null;
        $avgPos = $hasMetrics ? BlogGscMetric::query()->where('date', '>=', now()->subDays(28)->toDateString())->avg('position') : null;

        $topPriorities = Schema::hasTable('seo_recommendations')
            ? SeoRecommendation::query()
                ->whereIn('status', ['NEW', 'REVIEWED', 'APPROVED'])
                ->orderByDesc('priority_score')
                ->limit(10)
                ->get()
            : collect();

        return [
            'data_quality' => [
                'gsc_status' => $gscConfigured ? ($hasMetrics ? 'OK' : 'CONFIGURED_NO_ROWS') : 'DATA_UNAVAILABLE',
                'message' => $hasMetrics
                    ? 'Showing first-party GSC aggregates only.'
                    : 'No invented metrics. Connect GSC or wait for sync.',
            ],
            'kpis' => [
                'organic_clicks_28d' => $clicks,
                'impressions_28d' => $impressions,
                'ctr_28d' => ($impressions && $impressions > 0 && $clicks !== null) ? round($clicks / $impressions, 4) : null,
                'avg_position_28d' => $avgPos !== null ? round((float) $avgPos, 2) : null,
                'indexed_published' => BlogPost::published()->count(),
                'drafts' => BlogPost::query()->where('is_published', false)->count(),
            ],
            'content_health' => Schema::hasTable('seo_content_health') ? [
                'healthy' => SeoContentHealth::query()->where('health', 'healthy')->count(),
                'needs_update' => SeoContentHealth::query()->where('health', 'needs_update')->count(),
                'critical' => SeoContentHealth::query()->where('health', 'critical')->count(),
                'orphan_risk' => SeoContentHealth::query()->where('orphan_risk', true)->count(),
                'portfolio' => [
                    'STAR' => SeoContentHealth::query()->where('portfolio', 'STAR')->count(),
                    'GROW' => SeoContentHealth::query()->where('portfolio', 'GROW')->count(),
                    'MAINTAIN' => SeoContentHealth::query()->where('portfolio', 'MAINTAIN')->count(),
                    'FIX' => SeoContentHealth::query()->where('portfolio', 'FIX')->count(),
                    'RETIRE' => SeoContentHealth::query()->where('portfolio', 'RETIRE')->count(),
                ],
            ] : null,
            'top_priorities' => $topPriorities,
            'alerts' => Schema::hasTable('seo_alerts')
                ? SeoAlert::query()->where('is_resolved', false)
                    ->orderByRaw("CASE severity WHEN 'critical' THEN 1 WHEN 'high' THEN 2 WHEN 'medium' THEN 3 ELSE 4 END")
                    ->limit(10)->get()
                : [],
            'topics' => Schema::hasTable('seo_topic_scores')
                ? SeoTopicScore::query()->orderByDesc('topic_score')->limit(12)->get()
                : [],
            'opportunities_by_type' => Schema::hasTable('seo_opportunities')
                ? SeoOpportunity::query()->selectRaw('type, count(*) as total')->groupBy('type')->pluck('total', 'type')
                : [],
            'top_queries' => Schema::hasTable('seo_search_queries')
                ? SeoSearchQuery::query()->orderByDesc('impressions_28d')->limit(10)->get(['query_raw', 'clicks_28d', 'impressions_28d', 'ctr_28d', 'position_28d', 'current_page_url', 'intent', 'topic'])
                : [],
            'forecast' => [
                'note' => 'Forecast disabled until ≥90 days of first-party data exists. Not a guarantee.',
                'value' => null,
            ],
        ];
    }

    /** @return array<string, mixed> */
    public function buildWeeklyReport(): array
    {
        $payload = $this->executive();
        $payload['period'] = [
            'type' => 'weekly',
            'start' => now()->subDays(7)->toDateString(),
            'end' => now()->toDateString(),
        ];
        if (Schema::hasTable('seo_report_snapshots')) {
            SeoReportSnapshot::query()->create([
                'period_type' => 'weekly',
                'period_start' => now()->subDays(7)->toDateString(),
                'period_end' => now()->toDateString(),
                'payload' => $payload,
            ]);
        }

        return $payload;
    }
}
