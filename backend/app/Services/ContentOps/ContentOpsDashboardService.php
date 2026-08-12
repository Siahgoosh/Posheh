<?php

namespace App\Services\ContentOps;

use App\Models\BlogPost;
use App\Models\Content\ContentAiJob;
use App\Models\Content\ContentClaim;
use App\Models\Content\ContentOpsReport;
use App\Models\Seo\SeoOpportunity;
use Illuminate\Support\Facades\Schema;

class ContentOpsDashboardService
{
    public function __construct(
        private readonly ContentAiCostControlService $costs,
        private readonly ContentAiProviderRegistry $providers,
        private readonly ContentRefreshEngine $refresh,
    ) {}

    /** @return array<string,mixed> */
    public function executive(): array
    {
        if (! Schema::hasTable('content_ai_jobs')) {
            return ['note' => 'Run migration 2026_08_12_170000'];
        }

        $opsCounts = [];
        if (Schema::hasColumn('blog_posts', 'ops_status')) {
            $opsCounts = BlogPost::query()
                ->selectRaw('ops_status, count(*) as c')
                ->whereNotNull('ops_status')
                ->groupBy('ops_status')
                ->pluck('c', 'ops_status');
        }

        $jobs = [
            'queued' => ContentAiJob::query()->where('status', 'queued')->count(),
            'running' => ContentAiJob::query()->where('status', 'running')->count(),
            'retrying' => ContentAiJob::query()->where('status', 'retrying')->count(),
            'failed' => ContentAiJob::query()->where('status', 'failed')->count(),
            'completed' => ContentAiJob::query()->where('status', 'completed')->count(),
        ];

        $reviewQueue = BlogPost::query()
            ->where(function ($q) {
                $q->whereIn('ops_status', ['EDITOR_REVIEW', 'FACT_CHECK', 'SEO_REVIEW', 'CHANGES_REQUESTED'])
                    ->orWhereIn('review_status', ['in_review', 'seo_review', 'content_review']);
            })
            ->orderByDesc('updated_at')
            ->limit(20)
            ->get(['id', 'title', 'slug', 'ops_status', 'review_status', 'ops_priority', 'updated_at']);

        $highRiskClaims = Schema::hasTable('content_claims')
            ? ContentClaim::query()->where('requires_human', true)->whereNotIn('status', ['approved', 'rejected'])->count()
            : 0;

        $refreshNeeded = BlogPost::query()
            ->where('is_published', true)
            ->where(function ($q) {
                $q->whereIn('freshness_class', ['aging', 'outdated'])
                    ->orWhere('orphan_flag', true);
            })
            ->count();

        $opportunities = Schema::hasTable('seo_opportunities')
            ? SeoOpportunity::query()->orderByDesc('priority_score')->limit(10)->get(['id', 'type', 'title', 'slug', 'priority_score', 'status'])
            : [];

        $roadmap = $this->roadmap();

        return [
            'pipeline_counts' => $opsCounts,
            'jobs' => $jobs,
            'review_queue' => $reviewQueue,
            'high_risk_claims' => $highRiskClaims,
            'refresh_needed' => $refreshNeeded,
            'top_opportunities' => $opportunities,
            'ai_usage' => $this->costs->usageSummary(),
            'providers' => $this->providers->list(),
            'roadmap' => $roadmap,
            'policies' => [
                'auto_publish' => (bool) config('content_ops.allow_auto_publish', false),
                'auto_insert_links' => (bool) config('content_ops.allow_auto_insert_links', false),
                'auto_publish_images' => (bool) config('content_ops.allow_auto_publish_images', false),
            ],
            'note' => 'Internal Content OS metrics — not a Google Score. AI is assistant-only.',
        ];
    }

    /** @return array<string,mixed> */
    public function roadmap(): array
    {
        $base = BlogPost::query()->where('is_published', false);
        $p0 = (clone $base)->where('ops_priority', 'P0')->count();
        $p1 = (clone $base)->where('ops_priority', 'P1')->count();

        return [
            '7_days' => ['focus' => 'P0 quick wins + decaying published pages', 'p0' => $p0],
            '30_days' => ['focus' => 'P1 briefs → drafts → human review', 'p1' => $p1],
            '90_days' => ['focus' => 'Cluster balance + topical coverage + refresh backlog'],
        ];
    }

    /** @return array<string,mixed> */
    public function buildWeeklyReport(): array
    {
        $start = now()->subDays(7)->startOfDay();
        $end = now()->endOfDay();
        $published = BlogPost::query()->where('is_published', true)->whereBetween('published_at', [$start, $end])->count();
        $updated = BlogPost::query()->whereBetween('content_updated_at', [$start, $end])->count();
        $failedJobs = ContentAiJob::query()->where('status', 'failed')->whereBetween('created_at', [$start, $end])->count();
        $decay = $this->refresh->scanDecayAlerts();

        $payload = [
            'published' => $published,
            'updated' => $updated,
            'failed_ai_jobs' => $failedJobs,
            'decay_flagged' => $decay,
            'top_opportunities' => Schema::hasTable('seo_opportunities')
                ? SeoOpportunity::query()->orderByDesc('priority_score')->limit(10)->get(['type', 'title', 'slug', 'priority_score'])
                : [],
            'ai_cost' => $this->costs->usageSummary()['month'] ?? [],
            'note' => 'Weekly Content Ops — no ranking guarantees',
        ];

        if (Schema::hasTable('content_ops_reports')) {
            ContentOpsReport::query()->updateOrCreate(
                [
                    'type' => 'weekly',
                    'period_start' => $start->toDateString(),
                    'period_end' => $end->toDateString(),
                ],
                ['payload' => $payload]
            );
        }

        return $payload;
    }

    /** @return array<string,mixed> */
    public function buildMonthlyReport(): array
    {
        $start = now()->subDays(30)->startOfDay();
        $end = now()->endOfDay();
        $payload = [
            'published' => BlogPost::query()->whereBetween('published_at', [$start, $end])->where('is_published', true)->count(),
            'updated' => BlogPost::query()->whereBetween('content_updated_at', [$start, $end])->count(),
            'refreshed' => BlogPost::query()->where('ops_status', 'UPDATED')->whereBetween('updated_at', [$start, $end])->count(),
            'ai_usage' => $this->costs->usageSummary(),
            'cluster_balance' => BlogPost::query()
                ->selectRaw('category_slug, count(*) as c')
                ->where('is_published', true)
                ->groupBy('category_slug')
                ->orderByDesc('c')
                ->limit(15)
                ->pluck('c', 'category_slug'),
            'field_metrics' => [
                'clicks' => 'UNKNOWN',
                'leads' => 'UNKNOWN',
                'roi' => 'UNKNOWN',
                'note' => 'Shown only when real GSC/CRM revenue data exists',
            ],
        ];

        if (Schema::hasTable('content_ops_reports')) {
            ContentOpsReport::query()->updateOrCreate(
                [
                    'type' => 'monthly',
                    'period_start' => $start->toDateString(),
                    'period_end' => $end->toDateString(),
                ],
                ['payload' => $payload]
            );
        }

        return $payload;
    }
}
