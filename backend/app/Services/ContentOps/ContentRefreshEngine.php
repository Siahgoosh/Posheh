<?php

namespace App\Services\ContentOps;

use App\Models\BlogPost;
use App\Models\Content\ContentAiJob;
use App\Models\Seo\SeoContentHealth;
use App\Models\Seo\SeoOpportunity;
use Illuminate\Support\Facades\Schema;

/**
 * Suggests surgical refreshes — never full rewrite without reason.
 */
class ContentRefreshEngine
{
    /** @return array<string,mixed> */
    public function run(ContentAiJob $job): array
    {
        $post = BlogPost::findOrFail($job->blog_post_id);
        $freshness = $this->classify($post);
        $suggestions = [];

        $ageDays = $post->content_updated_at
            ? $post->content_updated_at->diffInDays(now())
            : ($post->published_at ? $post->published_at->diffInDays(now()) : 999);

        if ($ageDays > 180) {
            $suggestions[] = ['area' => 'freshness', 'change' => 'به‌روزرسانی تاریخ بازبینی و بخش‌های زمانی', 'priority' => 'P1'];
        }
        if (! $post->faq || (is_array($post->faq) && count($post->faq) < 2)) {
            $suggestions[] = ['area' => 'faq', 'change' => 'افزودن FAQ واقعی کاربران', 'priority' => 'P2'];
        }
        $internal = preg_match_all('/href=["\']\/blog\/[^"\']+["\']/i', (string) $post->content) ?: 0;
        if ($internal < 2) {
            $suggestions[] = ['area' => 'internal_links', 'change' => 'تقویت لینک داخلی موضوعی', 'priority' => 'P1'];
        }
        if (! $post->cover_image) {
            $suggestions[] = ['area' => 'images', 'change' => 'افزودن تصویر شاخص با Alt توصیفی', 'priority' => 'P2'];
        }
        if (! $post->cta_text) {
            $suggestions[] = ['area' => 'cta', 'change' => 'افزودن CTA مرتبط با intent', 'priority' => 'P2'];
        }

        $decay = null;
        if (Schema::hasTable('seo_content_health') && Schema::hasColumn('seo_content_health', 'health')) {
            $health = SeoContentHealth::query()->where('slug', $post->slug)->first();
            // Column is `health` (healthy|needs_update|critical|unknown) — NOT `status`
            $isDecaying = $health && (
                in_array((string) $health->health, ['needs_update', 'critical'], true)
                || ((float) ($health->decay_pct ?? 0) <= -20)
            );
            if ($isDecaying) {
                $decay = $health->toArray();
                $suggestions[] = ['area' => 'decay', 'change' => 'REFRESH_RECOMMENDED — افت عملکرد شناسایی شد', 'priority' => 'P0'];
            }
        }
        if (Schema::hasTable('seo_opportunities')) {
            $opp = SeoOpportunity::query()
                ->where('slug', $post->slug)
                ->whereIn('type', ['decay', 'content_decay', 'ctr', 'ctr_issue', 'quick_win'])
                ->orderByDesc('priority_score')
                ->first();
            if ($opp) {
                $suggestions[] = [
                    'area' => 'opportunity',
                    'change' => $opp->recommendation ?: $opp->title,
                    'priority' => 'P0',
                    'evidence' => $opp->evidence,
                ];
            }
        }

        if (Schema::hasColumn('blog_posts', 'freshness_class')) {
            $post->freshness_class = $freshness;
            $post->save();
        }

        return [
            'text' => 'Refresh plan (surgical — not full rewrite)',
            'structured' => [
                'freshness_class' => $freshness,
                'age_days' => $ageDays,
                'suggestions' => $suggestions,
                'decay' => $decay,
                'diff_policy' => 'Show Old/New before applying; AI must not rewrite entire article without reason',
                'note' => 'No fabricated new facts/statistics',
            ],
            'prompt_tokens' => 8,
            'completion_tokens' => 25,
            'model' => 'refresh-local',
            'provider' => 'local',
            'confidence' => 0.8,
        ];
    }

    public function classify(BlogPost $post): string
    {
        $ref = $post->content_updated_at ?? $post->published_at ?? $post->updated_at;
        if (! $ref) {
            return 'outdated';
        }
        $days = $ref->diffInDays(now());
        if ($days <= 60) {
            return 'fresh';
        }
        if ($days <= 180) {
            return 'stable';
        }
        if ($days <= 365) {
            return 'aging';
        }

        return 'outdated';
    }

    /** Mark decaying posts from SEO health */
    public function scanDecayAlerts(int $limit = 50): int
    {
        if (! Schema::hasTable('seo_content_health') || ! Schema::hasColumn('blog_posts', 'freshness_class')) {
            return 0;
        }
        // Guard: production schema uses `health`, never `status`
        if (! Schema::hasColumn('seo_content_health', 'health')) {
            return 0;
        }

        $n = 0;
        $rows = SeoContentHealth::query()
            ->where(function ($q) {
                $q->whereIn('health', ['needs_update', 'critical'])
                    ->orWhere(function ($qq) {
                        if (Schema::hasColumn('seo_content_health', 'decay_pct')) {
                            $qq->whereNotNull('decay_pct')->where('decay_pct', '<=', -20);
                        }
                    });
            })
            ->orderByRaw("CASE health WHEN 'critical' THEN 1 WHEN 'needs_update' THEN 2 ELSE 3 END")
            ->limit($limit)
            ->get();

        foreach ($rows as $h) {
            $post = BlogPost::query()->where('slug', $h->slug)->first();
            if (! $post) {
                continue;
            }
            $post->freshness_class = 'outdated';
            if (! $post->ops_priority || $post->ops_priority === 'P3') {
                $post->ops_priority = 'P1';
            }
            $post->save();
            $n++;
        }

        return $n;
    }
}
