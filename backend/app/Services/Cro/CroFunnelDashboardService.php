<?php

namespace App\Services\Cro;

use App\Models\AnalyticsEvent;
use App\Models\BlogPost;
use App\Models\Cro\CroConversionEvent;
use App\Models\Cro\CroCta;
use App\Models\Cro\CroCtaRule;
use App\Models\Cro\CroLead;
use App\Models\Cro\CroPageGoal;
use App\Models\Seo\SeoContentHealth;
use Illuminate\Support\Facades\Schema;

class CroFunnelDashboardService
{
    /** @return array<string, mixed> */
    public function executive(int $days = 28): array
    {
        $since = now()->subDays($days);
        $hasEvents = Schema::hasTable('cro_conversion_events');
        $hasLeads = Schema::hasTable('cro_leads');

        $countEvent = fn (string $type) => $hasEvents
            ? CroConversionEvent::query()->where('event_type', $type)->where('created_at', '>=', $since)->count()
            : null;

        $visitors = Schema::hasTable('analytics_events')
            ? (int) AnalyticsEvent::query()->where('event_type', 'page_view')->where('created_at', '>=', $since)->selectRaw('count(distinct visitor_hash) as c')->value('c')
            : null;

        $leads = $hasLeads ? CroLead::query()->where('created_at', '>=', $since)->count() : null;
        $qualified = $hasLeads ? CroLead::query()->where('created_at', '>=', $since)->whereIn('status', ['QUALIFIED', 'NEGOTIATION', 'WON'])->count() : null;
        $customers = $hasLeads ? CroLead::query()->where('created_at', '>=', $since)->where('status', 'WON')->count() : null;

        $ctaClicks = $countEvent('cta_click');
        $formStarts = $countEvent('form_start');
        $formSubmits = $countEvent('form_submit');

        return [
            'period_days' => $days,
            'funnel' => [
                'visitors' => $visitors,
                'cta_views' => $countEvent('cta_view'),
                'cta_clicks' => $ctaClicks,
                'form_starts' => $formStarts,
                'form_submits' => $formSubmits,
                'leads' => $leads,
                'qualified_leads' => $qualified,
                'customers' => $customers,
                'revenue' => null, // UNKNOWN without CRM revenue data
            ],
            'rates' => [
                'visitor_to_lead' => $this->rate($visitors, $leads),
                'lead_to_qualified' => $this->rate($leads, $qualified),
                'qualified_to_customer' => $this->rate($qualified, $customers),
            ],
            'lead_quality' => $hasLeads ? [
                'NEW' => CroLead::query()->where('status', 'NEW')->count(),
                'CONTACTED' => CroLead::query()->where('status', 'CONTACTED')->count(),
                'QUALIFIED' => CroLead::query()->where('status', 'QUALIFIED')->count(),
                'NEGOTIATION' => CroLead::query()->where('status', 'NEGOTIATION')->count(),
                'WON' => CroLead::query()->where('status', 'WON')->count(),
                'LOST' => CroLead::query()->where('status', 'LOST')->count(),
                'avg_response_seconds' => CroLead::query()->whereNotNull('response_seconds')->avg('response_seconds'),
                'by_source' => CroLead::query()->selectRaw('source, count(*) as total')->groupBy('source')->pluck('total', 'source'),
                'quality_feedback' => CroLead::query()->whereNotNull('quality_feedback')->selectRaw('quality_feedback, count(*) as total')->groupBy('quality_feedback')->pluck('total', 'quality_feedback'),
            ] : null,
            'top_converting_articles' => $this->topConvertingArticles($since),
            'high_traffic_low_conversion' => $this->highTrafficLowConversion(),
            'low_traffic_high_conversion' => $this->lowTrafficHighConversion(),
            'weekly_actions' => $this->weeklyActions(),
            'data_quality' => [
                'revenue' => 'UNKNOWN',
                'note' => 'Numbers are first-party only. Missing metrics show as null/UNKNOWN — never fabricated.',
            ],
        ];
    }

    /** @return list<array<string, mixed>> */
    private function topConvertingArticles($since): array
    {
        if (! Schema::hasTable('cro_leads')) {
            return [];
        }

        return CroLead::query()
            ->where('created_at', '>=', $since)
            ->whereNotNull('article_slug')
            ->selectRaw("article_slug, count(*) as leads, avg(lead_score) as avg_score, sum(case when status in ('QUALIFIED','NEGOTIATION','WON') then 1 else 0 end) as qualified")
            ->groupBy('article_slug')
            ->orderByDesc('leads')
            ->limit(10)
            ->get()
            ->map(fn ($r) => [
                'article_slug' => $r->article_slug,
                'leads' => (int) $r->leads,
                'qualified' => (int) $r->qualified,
                'avg_score' => $r->avg_score !== null ? round((float) $r->avg_score, 1) : null,
            ])
            ->all();
    }

    /** @return list<array<string, mixed>> */
    private function highTrafficLowConversion(): array
    {
        if (! Schema::hasTable('seo_content_health') || ! Schema::hasTable('cro_leads')) {
            return [];
        }
        $out = [];
        $rows = SeoContentHealth::query()->where('impressions_28d', '>=', 100)->orderByDesc('impressions_28d')->limit(30)->get();
        foreach ($rows as $row) {
            $leads = CroLead::query()->where('article_slug', $row->slug)->where('created_at', '>=', now()->subDays(28))->count();
            if ($leads <= 1 && $row->impressions_28d >= 100) {
                $out[] = [
                    'slug' => $row->slug,
                    'impressions_28d' => $row->impressions_28d,
                    'leads_28d' => $leads,
                    'recommendation' => 'CRO Audit: CTA / Intent / Trust / Offer را بررسی کنید',
                ];
            }
        }

        return array_slice($out, 0, 5);
    }

    /** @return list<array<string, mixed>> */
    private function lowTrafficHighConversion(): array
    {
        if (! Schema::hasTable('cro_leads')) {
            return [];
        }
        $rows = CroLead::query()
            ->where('created_at', '>=', now()->subDays(28))
            ->whereNotNull('article_slug')
            ->selectRaw('article_slug, count(*) as leads')
            ->groupBy('article_slug')
            ->having('leads', '>=', 3)
            ->orderByDesc('leads')
            ->limit(20)
            ->get();
        $out = [];
        foreach ($rows as $row) {
            $health = Schema::hasTable('seo_content_health')
                ? SeoContentHealth::query()->where('slug', $row->article_slug)->first()
                : null;
            $impressions = (int) ($health->impressions_28d ?? 0);
            if ($impressions > 0 && $impressions < 80) {
                $out[] = [
                    'slug' => $row->article_slug,
                    'leads_28d' => (int) $row->leads,
                    'impressions_28d' => $impressions,
                    'recommendation' => 'SEO Growth Priority — تبدیل خوب، ترافیک کم',
                ];
            }
        }

        return array_slice($out, 0, 5);
    }

    /** @return list<array{rank: int, action: string}> */
    private function weeklyActions(): array
    {
        $actions = [];
        foreach ($this->highTrafficLowConversion() as $i => $item) {
            $actions[] = ['rank' => count($actions) + 1, 'action' => 'Optimize CTA on '.$item['slug']];
            if (count($actions) >= 10) {
                break;
            }
        }
        foreach ($this->lowTrafficHighConversion() as $item) {
            if (count($actions) >= 10) {
                break;
            }
            $actions[] = ['rank' => count($actions) + 1, 'action' => 'SEO push for high-converting '.$item['slug']];
        }
        if (count($actions) < 10) {
            $actions[] = ['rank' => count($actions) + 1, 'action' => 'Review NEW cro_leads response SLA'];
        }

        return array_slice($actions, 0, 10);
    }

    private function rate(?int $den, ?int $num): ?float
    {
        if ($den === null || $num === null || $den === 0) {
            return null;
        }

        return round($num / $den, 4);
    }
}
