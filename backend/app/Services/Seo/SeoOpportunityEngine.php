<?php

namespace App\Services\Seo;

use App\Models\BlogPost;
use App\Models\Seo\SeoContentHealth;
use App\Models\Seo\SeoInternalSearch;
use App\Models\Seo\SeoOpportunity;
use App\Models\Seo\SeoPageDaily;
use App\Models\Seo\SeoRecommendation;
use App\Models\Seo\SeoSearchQuery;
use App\Models\Seo\SeoTopicScore;
use Illuminate\Support\Facades\Schema;

class SeoOpportunityEngine
{
    public function __construct(
        private readonly SeoPriorityScorer $priority,
        private readonly SeoQueryIntelligence $intelligence,
    ) {}

    /** @return array{opportunities: int, recommendations: int, health: int, topics: int} */
    public function analyze(): array
    {
        $createdOpp = 0;
        $createdRec = 0;

        $createdOpp += $this->detectQuickWins();
        $createdOpp += $this->detectStrikingDistance();
        $createdOpp += $this->detectCtrIssues();
        $createdOpp += $this->detectCannibalization();
        $createdOpp += $this->detectContentGaps();
        $createdOpp += $this->detectZeroResults();
        $createdOpp += $this->detectLinkOpportunities();

        $health = $this->refreshContentHealth();
        $topics = $this->refreshTopicScores();
        $createdRec = $this->materializeRecommendations();

        return [
            'opportunities' => $createdOpp,
            'recommendations' => $createdRec,
            'health' => $health,
            'topics' => $topics,
        ];
    }

    private function detectQuickWins(): int
    {
        if (! Schema::hasTable('seo_search_queries')) {
            return 0;
        }
        $minImp = (int) config('seo.quick_win.min_impressions', 50);
        $maxCtr = (float) config('seo.quick_win.max_ctr', 0.05);
        $count = 0;

        $rows = SeoSearchQuery::query()
            ->whereBetween('position_28d', [4, 10])
            ->where('impressions_28d', '>=', $minImp)
            ->where(function ($q) use ($maxCtr) {
                $q->whereNull('ctr_28d')->orWhere('ctr_28d', '<=', $maxCtr);
            })
            ->orderByDesc('impressions_28d')
            ->limit(50)
            ->get();

        foreach ($rows as $row) {
            $impact = min(95, 40 + (int) round($row->impressions_28d / 20));
            $score = $this->priority->score($impact, 70, 'low');
            $this->upsertOpportunity([
                'type' => 'quick_win',
                'title' => 'Quick Win: CTR/Title برای «'.$row->query_raw.'»',
                'page_url' => $row->current_page_url,
                'slug' => $this->slugFromUrl((string) $row->current_page_url),
                'query' => $row->query_raw,
                'topic' => $row->topic,
                'evidence' => "Position {$row->position_28d}, impressions {$row->impressions_28d}, CTR ".($row->ctr_28d ?? 'UNKNOWN'),
                'recommendation' => 'بهینه‌سازی Title و Meta بدون کلیک‌بیت؛ FAQ و پاسخ اول را تقویت کنید.',
                'expected_benefit' => 'بهبود بالقوه CTR در صفحه ۱',
                'risk' => 'low',
                'effort' => 'low',
                'impact' => $impact,
                'confidence' => 70,
                'priority_score' => $score,
                'payload' => ['source' => 'gsc_query', 'query_id' => $row->id],
            ]);
            $count++;
        }

        return $count;
    }

    private function detectStrikingDistance(): int
    {
        if (! Schema::hasTable('seo_search_queries')) {
            return 0;
        }
        $minImp = (int) config('seo.striking.min_impressions', 40);
        $count = 0;
        $rows = SeoSearchQuery::query()
            ->whereBetween('position_28d', [11, 20])
            ->where('impressions_28d', '>=', $minImp)
            ->orderByDesc('impressions_28d')
            ->limit(40)
            ->get();

        foreach ($rows as $row) {
            $impact = min(90, 35 + (int) round($row->impressions_28d / 25));
            $score = $this->priority->score($impact, 60, 'medium');
            $this->upsertOpportunity([
                'type' => 'striking_distance',
                'title' => 'Striking Distance: «'.$row->query_raw.'»',
                'page_url' => $row->current_page_url,
                'slug' => $this->slugFromUrl((string) $row->current_page_url),
                'query' => $row->query_raw,
                'topic' => $row->topic,
                'evidence' => "Position {$row->position_28d}, impressions {$row->impressions_28d}",
                'recommendation' => 'گسترش محتوا، لینک داخلی، FAQ و هم‌راستایی Intent.',
                'expected_benefit' => 'حرکت بالقوه به صفحه ۱',
                'risk' => 'low',
                'effort' => 'medium',
                'impact' => $impact,
                'confidence' => 60,
                'priority_score' => $score,
            ]);
            $count++;
        }

        return $count;
    }

    private function detectCtrIssues(): int
    {
        if (! Schema::hasTable('seo_search_queries')) {
            return 0;
        }
        $count = 0;
        $rows = SeoSearchQuery::query()
            ->where('position_28d', '<=', 5)
            ->where('impressions_28d', '>=', 80)
            ->where('ctr_28d', '<', 0.04)
            ->limit(30)
            ->get();

        foreach ($rows as $row) {
            $score = $this->priority->score(80, 75, 'low');
            $this->upsertOpportunity([
                'type' => 'ctr',
                'title' => 'CTR پایین با رتبه خوب: «'.$row->query_raw.'»',
                'page_url' => $row->current_page_url,
                'slug' => $this->slugFromUrl((string) $row->current_page_url),
                'query' => $row->query_raw,
                'topic' => $row->topic,
                'evidence' => "Position {$row->position_28d}, CTR {$row->ctr_28d}, impressions {$row->impressions_28d}",
                'recommendation' => 'بازبینی Title/Meta؛ از کلیک‌بیت پرهیز کنید. در صورت داده کافی Title Experiment با Approval.',
                'expected_benefit' => 'CTR بالاتر بدون تغییر URL',
                'risk' => 'low',
                'effort' => 'low',
                'impact' => 80,
                'confidence' => 75,
                'priority_score' => $score,
            ]);
            $count++;
        }

        return $count;
    }

    private function detectCannibalization(): int
    {
        if (! Schema::hasTable('seo_search_queries')) {
            return 0;
        }
        $count = 0;
        $clusters = SeoSearchQuery::query()
            ->whereNotNull('cluster_key')
            ->where('impressions_28d', '>=', 20)
            ->get()
            ->groupBy('cluster_key')
            ->filter(fn ($g) => $g->pluck('current_page_url')->filter()->unique()->count() >= 2);

        foreach ($clusters as $key => $group) {
            $pages = $group->pluck('current_page_url')->filter()->unique()->values();
            if ($pages->count() < 2) {
                continue;
            }
            $primary = $group->sortBy('position_28d')->first();
            $score = $this->priority->score(65, 55, 'high');
            $this->upsertOpportunity([
                'type' => 'cannibalization',
                'title' => 'ریسک Cannibalization: '.$key,
                'page_url' => $primary?->current_page_url,
                'slug' => $this->slugFromUrl((string) ($primary?->current_page_url ?? '')),
                'query' => $primary?->query_raw,
                'topic' => $primary?->topic,
                'evidence' => 'چند URL برای cluster مشابه Impression می‌گیرند: '.$pages->implode(', '),
                'recommendation' => 'Primary را مشخص کنید؛ سپس Merge/Reposition/Internal Linking/Differentiation. بدون تأیید مدیر Merge نکنید.',
                'expected_benefit' => 'تمرکز سیگنال رتبه روی Primary',
                'risk' => 'high',
                'effort' => 'high',
                'impact' => 65,
                'confidence' => 55,
                'priority_score' => $score,
                'payload' => ['pages' => $pages->all(), 'cluster' => $key],
            ]);
            $count++;
        }

        return $count;
    }

    private function detectContentGaps(): int
    {
        if (! Schema::hasTable('seo_search_queries')) {
            return 0;
        }
        $count = 0;
        $rows = SeoSearchQuery::query()
            ->where('impressions_28d', '>=', 30)
            ->where('business_value', '>=', 60)
            ->orderByDesc('impressions_28d')
            ->limit(40)
            ->get();

        foreach ($rows as $row) {
            $slug = $this->slugFromUrl((string) $row->current_page_url);
            $exists = $slug ? BlogPost::query()->where('slug', $slug)->exists() : false;
            $focusHit = BlogPost::query()
                ->where('focus_keyword', 'like', '%'.mb_substr($row->query_normalized, 0, 40).'%')
                ->exists();
            if ($exists || $focusHit) {
                continue;
            }
            $score = $this->priority->score((int) $row->business_value, 50, 'medium');
            $this->upsertOpportunity([
                'type' => 'content_gap',
                'title' => 'Content Gap: «'.$row->query_raw.'»',
                'page_url' => $row->current_page_url,
                'query' => $row->query_raw,
                'topic' => $row->topic,
                'evidence' => "Impressions {$row->impressions_28d}, business_value {$row->business_value}, صفحه مناسب پیدا نشد",
                'recommendation' => 'قبل از مقاله جدید، مقالات موجود را برای Update بررسی کنید. در غیر این صورت Brief بسازید.',
                'expected_benefit' => 'پوشش Intent با صفحه اختصاصی',
                'risk' => 'medium',
                'effort' => 'medium',
                'impact' => (int) $row->business_value,
                'confidence' => 50,
                'priority_score' => $score,
            ]);
            $count++;
        }

        return $count;
    }

    private function detectZeroResults(): int
    {
        if (! Schema::hasTable('seo_internal_searches')) {
            return 0;
        }
        $count = 0;
        $rows = SeoInternalSearch::query()
            ->where('zero_result', true)
            ->where('searched_at', '>=', now()->subDays(30))
            ->selectRaw('query_normalized, max(query_raw) as query_raw, count(*) as hits')
            ->groupBy('query_normalized')
            ->having('hits', '>=', 2)
            ->orderByDesc('hits')
            ->limit(30)
            ->get();

        foreach ($rows as $row) {
            $class = $this->intelligence->classify((string) $row->query_raw);
            $score = $this->priority->score($class['business_value'], 65, 'medium');
            $this->upsertOpportunity([
                'type' => 'zero_result',
                'title' => 'جستجوی داخلی بدون نتیجه: «'.$row->query_raw.'»',
                'query' => $row->query_raw,
                'topic' => $class['topic'],
                'evidence' => "Zero-result hits={$row->hits} در ۳۰ روز",
                'recommendation' => 'مقاله موجود را به‌روز کنید یا فرصت محتوایی جدید (با Approval) بسازید.',
                'expected_benefit' => 'کاهش جستجوی بی‌نتیجه و رضایت کاربر',
                'risk' => 'low',
                'effort' => 'medium',
                'impact' => $class['business_value'],
                'confidence' => 65,
                'priority_score' => $score,
            ]);
            $count++;
        }

        return $count;
    }

    private function detectLinkOpportunities(): int
    {
        $count = 0;
        $posts = BlogPost::published()->limit(200)->get(['id', 'slug', 'title', 'pillar_slug', 'category_slug', 'related_slugs', 'content']);
        foreach ($posts as $post) {
            $related = is_array($post->related_slugs) ? $post->related_slugs : [];
            if (count($related) >= 3) {
                continue;
            }
            $candidates = $posts->where('category_slug', $post->category_slug)
                ->where('slug', '!=', $post->slug)
                ->take(3);
            foreach ($candidates as $candidate) {
                if (in_array($candidate->slug, $related, true)) {
                    continue;
                }
                if (str_contains((string) $post->content, '/blog/'.$candidate->slug)) {
                    continue;
                }
                $score = $this->priority->score(55, 60, 'low');
                $this->upsertOpportunity([
                    'type' => 'link',
                    'title' => 'لینک داخلی پیشنهادی: '.$post->slug.' → '.$candidate->slug,
                    'slug' => $post->slug,
                    'page_url' => '/blog/'.$post->slug,
                    'topic' => $post->category_slug,
                    'evidence' => 'هم‌دسته هستند ولی لینک متنی/related کافی ندارند.',
                    'recommendation' => 'لینک descriptive اضافه کنید یا related_slugs را تکمیل کنید. مدیر Accept/Reject کند.',
                    'expected_benefit' => 'تقویت Topic Authority و کاهش orphan',
                    'risk' => 'low',
                    'effort' => 'low',
                    'impact' => 55,
                    'confidence' => 60,
                    'priority_score' => $score,
                    'payload' => ['from' => $post->slug, 'to' => $candidate->slug],
                ]);
                $count++;
                break;
            }
        }

        return $count;
    }

    private function refreshContentHealth(): int
    {
        if (! Schema::hasTable('seo_content_health')) {
            return 0;
        }
        $n = 0;
        $posts = BlogPost::query()->limit(500)->get();
        foreach ($posts as $post) {
            $perf = SeoPageDaily::query()
                ->where('slug', $post->slug)
                ->where('date', '>=', now()->subDays(28)->toDateString())
                ->selectRaw('sum(clicks) as clicks, sum(impressions) as impressions, avg(position) as position')
                ->first();
            $prev = SeoPageDaily::query()
                ->where('slug', $post->slug)
                ->whereBetween('date', [now()->subDays(56)->toDateString(), now()->subDays(29)->toDateString()])
                ->selectRaw('sum(clicks) as clicks')
                ->first();

            $clicks = (int) ($perf->clicks ?? 0);
            $prevClicks = (float) ($prev->clicks ?? 0);
            $decayPct = null;
            $decayReason = null;
            if ($prevClicks >= 10) {
                $decayPct = round((($clicks - $prevClicks) / $prevClicks) * 100, 2);
                if ($decayPct <= -25) {
                    $decayReason = 'UNKNOWN';
                    if (! $post->content_updated_at || $post->content_updated_at < now()->subMonths(6)) {
                        $decayReason = 'OUTDATED';
                    }
                }
            }

            $related = is_array($post->related_slugs) ? $post->related_slugs : [];
            $orphan = $post->is_published && count($related) === 0;

            $portfolio = 'MAINTAIN';
            $lifecycle = $post->is_published ? 'PUBLISHED' : 'CREATED';
            $health = 'unknown';
            if ($clicks >= 50 && ($perf->impressions ?? 0) >= 200) {
                $portfolio = 'STAR';
                $lifecycle = 'MATURE';
                $health = 'healthy';
            } elseif ($decayPct !== null && $decayPct <= -25) {
                $portfolio = 'FIX';
                $lifecycle = 'DECAYING';
                $health = 'critical';
                $this->upsertOpportunity([
                    'type' => 'decay',
                    'title' => 'Content Decay: '.$post->slug,
                    'slug' => $post->slug,
                    'page_url' => '/blog/'.$post->slug,
                    'evidence' => "افت کلیک {$decayPct}% نسبت به ۲۸ روز قبل. reason={$decayReason}",
                    'recommendation' => 'Update Facts / Expand / FAQ / Internal Links — Major Rewrite فقط با Approval.',
                    'expected_benefit' => 'بازیابی ترافیک',
                    'risk' => 'medium',
                    'effort' => 'medium',
                    'impact' => 75,
                    'confidence' => $prevClicks >= 20 ? 70 : 40,
                    'priority_score' => $this->priority->score(75, $prevClicks >= 20 ? 70 : 40, 'medium'),
                ]);
            } elseif ($clicks > 0 && $clicks < 20 && ($perf->impressions ?? 0) >= 100) {
                $portfolio = 'GROW';
                $lifecycle = 'GROWING';
                $health = 'needs_update';
            } elseif (! $post->is_published) {
                $portfolio = 'MAINTAIN';
                $health = 'unknown';
            }

            $funnel = match ($post->search_intent) {
                'commercial', 'transactional' => 'BOFU',
                'informational' => 'TOFU',
                default => 'MOFU',
            };

            SeoContentHealth::query()->updateOrCreate(
                ['slug' => $post->slug],
                [
                    'blog_post_id' => $post->id,
                    'lifecycle' => $lifecycle,
                    'portfolio' => $portfolio,
                    'funnel_stage' => $funnel,
                    'health' => $health,
                    'clicks_28d' => $clicks,
                    'impressions_28d' => (int) ($perf->impressions ?? 0),
                    'ctr_28d' => ($perf && $perf->impressions > 0) ? round($clicks / $perf->impressions, 4) : null,
                    'position_28d' => $perf?->position ? round((float) $perf->position, 2) : null,
                    'clicks_prev_28d' => $prevClicks ?: null,
                    'decay_pct' => $decayPct,
                    'decay_reason' => $decayReason,
                    'orphan_risk' => $orphan,
                    'analyzed_at' => now(),
                ]
            );
            $n++;
        }

        return $n;
    }

    private function refreshTopicScores(): int
    {
        if (! Schema::hasTable('seo_topic_scores')) {
            return 0;
        }
        $groups = BlogPost::query()
            ->selectRaw('category_slug, count(*) as total, sum(case when is_published=1 then 1 else 0 end) as published')
            ->whereNotNull('category_slug')
            ->groupBy('category_slug')
            ->get();

        $n = 0;
        foreach ($groups as $g) {
            $pillar = BlogPost::query()->where('category_slug', $g->category_slug)->whereColumn('slug', 'pillar_slug')->exists()
                || BlogPost::query()->where('category_slug', $g->category_slug)->whereNotNull('pillar_slug')->where('slug', $g->category_slug)->exists();
            // simpler pillar: any post with matching pillar_slug equals its own slug in category
            $pillar = BlogPost::query()
                ->where('category_slug', $g->category_slug)
                ->whereNotNull('pillar_slug')
                ->whereColumn('slug', 'pillar_slug')
                ->exists();

            $perf = SeoContentHealth::query()
                ->whereIn('slug', BlogPost::query()->where('category_slug', $g->category_slug)->pluck('slug'))
                ->selectRaw('sum(clicks_28d) as clicks, sum(impressions_28d) as impressions, avg(position_28d) as position')
                ->first();

            $coverage = min(100, (int) $g->published * 8);
            $quality = min(100, 40 + (int) $g->published * 3);
            $authority = min(100, (int) (($perf->clicks ?? 0) / 5) + ($pillar ? 20 : 0));
            $topicScore = round(($coverage * 0.35) + ($quality * 0.25) + ($authority * 0.4), 2);

            SeoTopicScore::query()->updateOrCreate(
                ['topic' => $g->category_slug],
                [
                    'article_count' => (int) $g->total,
                    'clicks_28d' => (int) ($perf->clicks ?? 0),
                    'impressions_28d' => (int) ($perf->impressions ?? 0),
                    'avg_position' => $perf?->position ? round((float) $perf->position, 2) : null,
                    'has_pillar' => $pillar,
                    'supporting_count' => max(0, (int) $g->published - ($pillar ? 1 : 0)),
                    'coverage_score' => $coverage,
                    'quality_score' => $quality,
                    'authority_score' => $authority,
                    'topic_score' => $topicScore,
                    'gaps' => $pillar ? [] : ['missing_pillar'],
                    'analyzed_at' => now(),
                ]
            );

            if (! $pillar && ($perf->impressions ?? 0) >= 50) {
                $this->upsertOpportunity([
                    'type' => 'pillar',
                    'title' => 'Pillar Opportunity: '.$g->category_slug,
                    'topic' => $g->category_slug,
                    'evidence' => 'Topic بدون pillar مشخص، impressions='.($perf->impressions ?? 0),
                    'recommendation' => 'یک مقاله پیلار قوی تعریف/ارتقا دهید؛ supportingها را به آن لینک کنید.',
                    'expected_benefit' => 'Topical Authority',
                    'risk' => 'medium',
                    'effort' => 'high',
                    'impact' => 70,
                    'confidence' => 55,
                    'priority_score' => $this->priority->score(70, 55, 'high'),
                ]);
            }
            $n++;
        }

        return $n;
    }

    private function materializeRecommendations(): int
    {
        if (! Schema::hasTable('seo_recommendations') || ! Schema::hasTable('seo_opportunities')) {
            return 0;
        }
        $n = 0;
        $opps = SeoOpportunity::query()
            ->whereIn('status', ['NEW', 'REVIEWED'])
            ->orderByDesc('priority_score')
            ->limit(100)
            ->get();

        foreach ($opps as $opp) {
            $action = match ($opp->type) {
                'quick_win', 'ctr' => 'title_opt',
                'striking_distance', 'decay' => 'expand',
                'content_gap', 'zero_result' => 'refresh',
                'link' => 'internal_link',
                'cannibalization' => 'merge',
                'pillar' => 'expand',
                default => 'refresh',
            };
            $automation = in_array($action, ['merge', 'rewrite'], true) ? 'MANUAL' : 'ASSISTED';
            $exists = SeoRecommendation::query()
                ->where('seo_opportunity_id', $opp->id)
                ->whereNotIn('status', ['REJECTED', 'ROLLED_BACK'])
                ->exists();
            if ($exists) {
                continue;
            }
            SeoRecommendation::query()->create([
                'seo_opportunity_id' => $opp->id,
                'action_type' => $action,
                'automation_level' => $automation,
                'priority' => $this->priority->priorityLabel((float) $opp->priority_score),
                'problem' => $opp->title,
                'evidence' => (string) $opp->evidence,
                'recommendation' => (string) $opp->recommendation,
                'expected_benefit' => $opp->expected_benefit,
                'risk' => $opp->risk,
                'effort' => $opp->effort,
                'priority_score' => $opp->priority_score,
                'status' => 'NEW',
                'payload' => $opp->payload,
            ]);
            $n++;
        }

        return $n;
    }

    /** @param array<string, mixed> $data */
    private function upsertOpportunity(array $data): void
    {
        if (! Schema::hasTable('seo_opportunities')) {
            return;
        }
        $fingerprint = ($data['type'] ?? '').'|'.($data['slug'] ?? '').'|'.($data['query'] ?? $data['title'] ?? '');
        $existing = SeoOpportunity::query()
            ->where('type', $data['type'])
            ->where('title', $data['title'])
            ->whereIn('status', ['NEW', 'REVIEWED', 'APPROVED'])
            ->first();

        $payload = array_merge($data['payload'] ?? [], ['fingerprint' => sha1($fingerprint)]);
        $data['payload'] = $payload;
        $data['detected_at'] = now();
        $data['status'] = $data['status'] ?? 'NEW';

        if ($existing) {
            $existing->update(collect($data)->except(['status'])->all());
        } else {
            SeoOpportunity::query()->create($data);
        }
    }

    private function slugFromUrl(string $url): ?string
    {
        if (preg_match('#/blog/([a-z0-9\-]+)#i', $url, $m)) {
            return $m[1];
        }

        return null;
    }
}
