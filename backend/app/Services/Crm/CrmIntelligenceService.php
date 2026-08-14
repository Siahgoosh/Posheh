<?php

namespace App\Services\Crm;

use App\Models\Commission;
use App\Models\CrmDeal;
use App\Models\CrmOffer;
use App\Models\CrmNegotiation;
use App\Models\CrmPipelineProbability;
use App\Models\CrmPropertyPresentation;
use App\Models\CrmPropertyFeedback;
use App\Models\Customer;
use App\Models\Property;
use App\Models\PropertyVisit;
use App\Models\User;
use App\Enums\PropertyStatus;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 3 — Executive / Agent / Funnel / Forecast / Source / Property intelligence.
 * Extends ReportService aggregates; does not replace accounting as financial source of truth for commissions.
 */
class CrmIntelligenceService
{
    public function __construct(
        private readonly CrmBootstrapService $bootstrap,
        private readonly SalesQueueService $queue,
    ) {}

    /** @return array{from:Carbon,to:Carbon,prev_from:Carbon,prev_to:Carbon,label:string} */
    public function resolveRange(?string $period, ?string $from = null, ?string $to = null): array
    {
        $now = now();
        return match ($period) {
            'today' => [
                'from' => $now->copy()->startOfDay(),
                'to' => $now->copy()->endOfDay(),
                'prev_from' => $now->copy()->subDay()->startOfDay(),
                'prev_to' => $now->copy()->subDay()->endOfDay(),
                'label' => 'today',
            ],
            'yesterday' => [
                'from' => $now->copy()->subDay()->startOfDay(),
                'to' => $now->copy()->subDay()->endOfDay(),
                'prev_from' => $now->copy()->subDays(2)->startOfDay(),
                'prev_to' => $now->copy()->subDays(2)->endOfDay(),
                'label' => 'yesterday',
            ],
            'this_week' => [
                'from' => $now->copy()->startOfWeek(),
                'to' => $now->copy()->endOfWeek(),
                'prev_from' => $now->copy()->subWeek()->startOfWeek(),
                'prev_to' => $now->copy()->subWeek()->endOfWeek(),
                'label' => 'this_week',
            ],
            'last_week' => [
                'from' => $now->copy()->subWeek()->startOfWeek(),
                'to' => $now->copy()->subWeek()->endOfWeek(),
                'prev_from' => $now->copy()->subWeeks(2)->startOfWeek(),
                'prev_to' => $now->copy()->subWeeks(2)->endOfWeek(),
                'label' => 'last_week',
            ],
            'this_year' => [
                'from' => $now->copy()->startOfYear(),
                'to' => $now->copy()->endOfYear(),
                'prev_from' => $now->copy()->subYear()->startOfYear(),
                'prev_to' => $now->copy()->subYear()->endOfYear(),
                'label' => 'this_year',
            ],
            'custom' => [
                'from' => Carbon::parse($from ?? $now->copy()->startOfMonth()),
                'to' => Carbon::parse($to ?? $now),
                'prev_from' => Carbon::parse($from ?? $now->copy()->startOfMonth())->subMonth(),
                'prev_to' => Carbon::parse($to ?? $now)->subMonth(),
                'label' => 'custom',
            ],
            default => [ // this_month
                'from' => $now->copy()->startOfMonth(),
                'to' => $now->copy()->endOfMonth(),
                'prev_from' => $now->copy()->subMonth()->startOfMonth(),
                'prev_to' => $now->copy()->subMonth()->endOfMonth(),
                'label' => 'this_month',
            ],
        };
    }

    public function ensureProbabilities(int $officeId): void
    {
        if (! Schema::hasTable('crm_pipeline_probabilities')) {
            return;
        }
        $defaults = [
            'lead' => 10,
            'contact' => 20,
            'visit' => 40,
            'negotiation' => 75,
            'closed_won' => 100,
            'closed_lost' => 0,
            'qualified' => 25,
            'offer' => 60,
            'contract' => 90,
        ];
        foreach ($defaults as $key => $prob) {
            CrmPipelineProbability::firstOrCreate(
                ['office_id' => $officeId, 'stage_key' => $key],
                ['probability' => $prob]
            );
        }
    }

    public function probabilityMap(int $officeId): array
    {
        $this->ensureProbabilities($officeId);
        if (! Schema::hasTable('crm_pipeline_probabilities')) {
            return ['lead' => 10, 'contact' => 20, 'visit' => 40, 'negotiation' => 75, 'closed_won' => 100];
        }

        return CrmPipelineProbability::where('office_id', $officeId)->pluck('probability', 'stage_key')->all();
    }

    public function executiveDashboard(User $user, ?string $period = 'this_month'): array
    {
        $this->bootstrap->ensureForUser($user);
        $officeId = (int) $user->office_id;
        $cacheKey = "crm:exec:{$officeId}:{$period}:".($user->canManageOffice() ? 'm' : 'a'.$user->id);

        return Cache::remember($cacheKey, 60, function () use ($user, $period) {
            $range = $this->resolveRange($period);
            $kpis = $this->periodKpis($user, $range['from'], $range['to']);
            $prev = $this->periodKpis($user, $range['prev_from'], $range['prev_to']);
            $comparison = [];
            foreach ($kpis as $key => $val) {
                if (! is_numeric($val)) {
                    continue;
                }
                $p = (float) ($prev[$key] ?? 0);
                $comparison[$key] = [
                    'value' => $val,
                    'previous' => $p,
                    'delta_percent' => $p > 0 ? round((($val - $p) / $p) * 100, 1) : ($val > 0 ? 100.0 : 0.0),
                ];
            }

            $funnel = $this->funnelAnalytics($user);
            $forecast = $this->forecast($user);
            $bottlenecks = $this->detectBottlenecks($funnel);
            $opportunities = $this->queue->opportunities($user);
            $briefing = $this->queue->dailyBriefing($user);
            $agents = $user->canManageOffice() ? $this->agentLeaderboard($user) : [];
            $sources = $this->sourceIntelligence($user, $range['from'], $range['to']);
            $propertyIntel = $this->topProperties($user);

            return [
                'period' => $range['label'],
                'kpis' => $kpis,
                'comparison' => $comparison,
                'funnel' => $funnel,
                'bottlenecks' => $bottlenecks,
                'forecast' => $forecast,
                'briefing' => $briefing,
                'opportunities_summary' => [
                    'hot_leads' => count($opportunities['hot_leads'] ?? []),
                    'overdue' => count($opportunities['overdue_follow_ups'] ?? []),
                    'stalled' => count($opportunities['stalled_negotiations'] ?? []),
                    'pending_offers' => count($opportunities['pending_offers'] ?? []),
                    'reactivation' => count($opportunities['reactivation'] ?? []),
                ],
                'agents' => $agents,
                'sources' => $sources,
                'top_properties' => $propertyIntel,
                'generated_at' => now()->toIso8601String(),
            ];
        });
    }

    public function periodKpis(User $user, Carbon $from, Carbon $to): array
    {
        $officeId = (int) $user->office_id;
        $dealQ = CrmDeal::where('office_id', $officeId)
            ->when(! $user->canManageOffice(), fn ($q) => $q->where('assigned_to', $user->id));

        $created = (clone $dealQ)->whereBetween('created_at', [$from, $to]);
        $totalLeads = (clone $created)->count();
        $hot = (clone $dealQ)->whereNotIn('stage', ['closed_won', 'closed_lost'])->where('lead_score', '>=', 80)->count();
        $qualified = (clone $dealQ)->whereIn('stage', ['contact', 'visit', 'negotiation'])->count();
        $won = (clone $dealQ)->where('stage', 'closed_won')->whereBetween('updated_at', [$from, $to]);
        $lost = (clone $dealQ)->where('stage', 'closed_lost')->whereBetween('updated_at', [$from, $to])->count();
        $wonCount = $won->count();
        $wonValue = (int) (clone $won)->sum('value');
        $open = (clone $dealQ)->whereNotIn('stage', ['closed_won', 'closed_lost'])->count();

        $viewings = PropertyVisit::where('office_id', $officeId)
            ->when(! $user->canManageOffice(), fn ($q) => $q->where('assigned_to', $user->id))
            ->whereBetween('visit_at', [$from, $to])->count();

        $offers = Schema::hasTable('crm_offers')
            ? CrmOffer::where('office_id', $officeId)->whereBetween('created_at', [$from, $to])->count()
            : 0;
        $negotiations = Schema::hasTable('crm_negotiations')
            ? CrmNegotiation::where('office_id', $officeId)->whereIn('status', ['open', 'stalled'])->count()
            : 0;

        $commissionPending = (int) Commission::where('office_id', $officeId)->where('status', 'pending')->sum('commission_amount');
        $commissionPaid = (int) Commission::where('office_id', $officeId)->where('status', 'paid')
            ->whereBetween('paid_at', [$from, $to])->sum('commission_amount');

        $activeProperties = Property::where('office_id', $officeId)->where('status', PropertyStatus::Active)->count();
        $activeCustomers = Customer::where('office_id', $officeId)
            ->where(function ($q) {
                $q->whereNull('lifecycle')->orWhereNotIn('lifecycle', ['closed', 'past']);
            })->count();

        $avgDealValue = $wonCount > 0 ? (int) round($wonValue / $wonCount) : 0;
        $conversion = $totalLeads > 0 ? round($wonCount / max($totalLeads, 1) * 100, 1) : (
            $open + $wonCount > 0 ? round($wonCount / max($open + $wonCount, 1) * 100, 1) : 0
        );

        $avgCycleDays = $this->averageDealCycleDays($officeId, $from, $to, $user);

        return [
            'total_leads' => (clone $dealQ)->count(),
            'new_leads' => $totalLeads,
            'qualified_leads' => $qualified,
            'hot_leads' => $hot,
            'active_customers' => $activeCustomers,
            'active_properties' => $activeProperties,
            'viewings' => $viewings,
            'offers' => $offers,
            'negotiations' => $negotiations,
            'won_deals' => $wonCount,
            'lost_deals' => $lost,
            'revenue' => $wonValue,
            'commission_pending' => $commissionPending,
            'commission_collected' => $commissionPaid,
            'conversion_rate' => $conversion,
            'average_deal_value' => $avgDealValue,
            'average_deal_cycle_days' => $avgCycleDays,
            'open_deals' => $open,
        ];
    }

    private function averageDealCycleDays(int $officeId, Carbon $from, Carbon $to, User $user): float
    {
        $won = CrmDeal::where('office_id', $officeId)
            ->when(! $user->canManageOffice(), fn ($q) => $q->where('assigned_to', $user->id))
            ->where('stage', 'closed_won')
            ->whereBetween('updated_at', [$from, $to])
            ->get(['created_at', 'updated_at']);
        if ($won->isEmpty()) {
            return 0;
        }
        $days = $won->map(fn ($d) => max(0, $d->created_at->diffInDays($d->updated_at)))->avg();

        return round((float) $days, 1);
    }

    public function funnelAnalytics(User $user): array
    {
        $officeId = (int) $user->office_id;
        $order = [
            ['key' => 'lead', 'label' => 'سرنخ'],
            ['key' => 'contact', 'label' => 'تماس'],
            ['key' => 'visit', 'label' => 'بازدید'],
            ['key' => 'negotiation', 'label' => 'مذاکره'],
            ['key' => 'closed_won', 'label' => 'معامله'],
        ];

        $counts = CrmDeal::where('office_id', $officeId)
            ->when(! $user->canManageOffice(), fn ($q) => $q->where('assigned_to', $user->id))
            ->selectRaw('stage, count(*) as c')
            ->groupBy('stage')
            ->pluck('c', 'stage');

        // Cumulative-style funnel: count deals that reached at least this stage (simplified by current stage weights)
        $stageRank = ['lead' => 1, 'contact' => 2, 'visit' => 3, 'negotiation' => 4, 'closed_won' => 5, 'closed_lost' => 0];
        $all = CrmDeal::where('office_id', $officeId)
            ->when(! $user->canManageOffice(), fn ($q) => $q->where('assigned_to', $user->id))
            ->get(['stage', 'created_at', 'updated_at', 'first_contacted_at']);

        $rows = [];
        $prevCount = null;
        foreach ($order as $i => $stage) {
            $rank = $stageRank[$stage['key']] ?? 0;
            $count = $all->filter(function ($d) use ($rank, $stageRank, $stage) {
                $r = $stageRank[$d->stage] ?? 0;
                if ($stage['key'] === 'closed_won') {
                    return $d->stage === 'closed_won';
                }

                return $r >= $rank || $d->stage === $stage['key'];
            })->count();
            // Prefer current-stage snapshot for open stages when cumulative is odd
            if ($stage['key'] !== 'closed_won') {
                $current = (int) ($counts[$stage['key']] ?? 0);
                // use max of current and cumulative subset for visibility
                $count = max($current, (int) $all->filter(fn ($d) => ($stageRank[$d->stage] ?? 0) >= $rank && $d->stage !== 'closed_lost')->count());
            }

            $conversion = $prevCount && $prevCount > 0 ? round($count / $prevCount * 100, 1) : 100.0;
            $drop = $prevCount && $prevCount > 0 ? round((($prevCount - $count) / $prevCount) * 100, 1) : 0.0;
            $avgDays = $this->avgTimeInStageApprox($all, $stage['key'], $stageRank);

            $rows[] = [
                'stage' => $stage['key'],
                'label' => $stage['label'],
                'count' => $count,
                'conversion_percent' => $conversion,
                'drop_off_percent' => max(0, $drop),
                'average_duration_days' => $avgDays,
            ];
            $prevCount = max($count, 1);
        }

        return $rows;
    }

    private function avgTimeInStageApprox($deals, string $stage, array $rank): float
    {
        $subset = $deals->where('stage', $stage);
        if ($subset->isEmpty()) {
            return 0;
        }

        return round($subset->map(fn ($d) => max(0, $d->created_at->diffInDays($d->updated_at ?? now())))->avg(), 1);
    }

    public function detectBottlenecks(array $funnel): array
    {
        $alerts = [];
        for ($i = 1; $i < count($funnel); $i++) {
            $prev = $funnel[$i - 1];
            $cur = $funnel[$i];
            if ($cur['conversion_percent'] < 35 && $prev['count'] >= 5) {
                $alerts[] = [
                    'severity' => 'high',
                    'from' => $prev['stage'],
                    'to' => $cur['stage'],
                    'conversion_percent' => $cur['conversion_percent'],
                    'message' => sprintf(
                        'تبدیل %s → %s برابر %.1f٪ است — گلوگاه احتمالی: پیگیری / قیمت‌گذاری / واجدشرایط‌سازی',
                        $prev['label'],
                        $cur['label'],
                        $cur['conversion_percent']
                    ),
                ];
            }
        }

        return $alerts;
    }

    public function forecast(User $user): array
    {
        $officeId = (int) $user->office_id;
        $probs = $this->probabilityMap($officeId);
        $open = CrmDeal::where('office_id', $officeId)
            ->when(! $user->canManageOffice(), fn ($q) => $q->where('assigned_to', $user->id))
            ->whereNotIn('stage', ['closed_won', 'closed_lost'])
            ->get(['id', 'stage', 'value', 'probability', 'title']);

        $pipeline = 0;
        $weighted = 0;
        foreach ($open as $deal) {
            $value = (int) ($deal->value ?? 0);
            $p = $deal->probability !== null
                ? (int) $deal->probability
                : (int) ($probs[$deal->stage] ?? 10);
            $pipeline += $value;
            $weighted += (int) round($value * ($p / 100));
        }

        // Commission estimate from office settings if available — use 1% of weighted as soft default only via config table if present
        $rateBps = 1.0; // percent — never hardcode business rate as sole truth; office settings win
        try {
            if (Schema::hasTable('commission_settings')) {
                $settings = \App\Models\CommissionSetting::where('office_id', $officeId)->first();
                if ($settings && $settings->sale_rate_percent !== null) {
                    $rateBps = (float) $settings->sale_rate_percent;
                }
            }
        } catch (\Throwable) {
        }
        $expectedCommission = (int) round($weighted * ($rateBps / 100));

        return [
            'pipeline_value' => $pipeline,
            'weighted_pipeline' => $weighted,
            'open_deals' => $open->count(),
            'expected_commission' => $expectedCommission,
            'commission_rate_percent' => $rateBps,
            'near_close' => $open->filter(fn ($d) => in_array($d->stage, ['negotiation'], true))
                ->take(10)->values()->map(fn ($d) => [
                    'id' => $d->id,
                    'title' => $d->title,
                    'value' => $d->value,
                    'stage' => $d->stage,
                    'probability' => $d->probability ?? ($probs[$d->stage] ?? null),
                ])->all(),
        ];
    }

    public function agentLeaderboard(User $user): array
    {
        $officeId = (int) $user->office_id;
        $agents = User::where('office_id', $officeId)
            ->whereIn('role', ['consultant', 'office_manager'])
            ->where('is_active', true)
            ->get();

        return $agents->map(function (User $agent) use ($officeId) {
            $deals = CrmDeal::where('office_id', $officeId)->where('assigned_to', $agent->id);
            $won = (clone $deals)->where('stage', 'closed_won');
            $open = (clone $deals)->whereNotIn('stage', ['closed_won', 'closed_lost']);
            $total = (clone $deals)->count();
            $wonCount = $won->count();
            $revenue = (int) (clone $won)->sum('value');
            $viewings = PropertyVisit::where('office_id', $officeId)->where('assigned_to', $agent->id)->count();
            $offers = Schema::hasTable('crm_offers')
                ? CrmOffer::where('office_id', $officeId)->where('created_by', $agent->id)->count()
                : 0;
            $commission = (int) Commission::where('office_id', $officeId)->where('user_id', $agent->id)->sum('commission_amount');

            $withContact = CrmDeal::where('office_id', $officeId)->where('assigned_to', $agent->id)
                ->whereNotNull('first_contacted_at')->get(['created_at', 'first_contacted_at']);
            $avgResponse = $withContact->isEmpty() ? null : round(
                $withContact->map(fn ($d) => $d->created_at->diffInMinutes($d->first_contacted_at))->avg(),
                1
            );

            $conversion = $total > 0 ? round($wonCount / $total * 100, 1) : 0;
            $score = $this->agentPerformanceScore([
                'conversion' => $conversion,
                'revenue' => $revenue,
                'viewings' => $viewings,
                'response_minutes' => $avgResponse,
                'won' => $wonCount,
                'open' => $open->count(),
            ]);

            return [
                'agent_id' => $agent->id,
                'name' => $agent->name,
                'leads' => $total,
                'open_deals' => $open->count(),
                'won_deals' => $wonCount,
                'viewings' => $viewings,
                'offers' => $offers,
                'revenue' => $revenue,
                'commission' => $commission,
                'conversion_rate' => $conversion,
                'avg_response_minutes' => $avgResponse,
                'performance_score' => $score,
            ];
        })->sortByDesc('performance_score')->values()->all();
    }

    public function agentPerformanceScore(array $m): int
    {
        $score = 40;
        $score += min(25, (float) ($m['conversion'] ?? 0) * 0.4);
        $score += min(15, (int) ($m['won'] ?? 0) * 3);
        $score += min(10, (int) ($m['viewings'] ?? 0));
        $resp = $m['response_minutes'] ?? null;
        if ($resp !== null) {
            if ($resp <= 15) {
                $score += 10;
            } elseif ($resp <= 60) {
                $score += 5;
            } elseif ($resp > 1440) {
                $score -= 10;
            }
        }
        if (($m['open'] ?? 0) > 40) {
            $score -= 5; // overload penalty
        }

        return (int) max(0, min(100, round($score)));
    }

    public function agentDashboard(User $user): array
    {
        $exec = $this->executiveDashboard($user, 'this_month');
        $queue = $this->queue->todayQueue($user);

        return [
            'kpis' => $exec['kpis'],
            'forecast' => $exec['forecast'],
            'queue_summary' => $queue['summary'],
            'briefing' => $exec['briefing'],
            'my_score' => $this->agentPerformanceScore([
                'conversion' => $exec['kpis']['conversion_rate'] ?? 0,
                'revenue' => $exec['kpis']['revenue'] ?? 0,
                'viewings' => $exec['kpis']['viewings'] ?? 0,
                'won' => $exec['kpis']['won_deals'] ?? 0,
                'open' => $exec['kpis']['open_deals'] ?? 0,
                'response_minutes' => null,
            ]),
        ];
    }

    public function sourceIntelligence(User $user, ?Carbon $from = null, ?Carbon $to = null): array
    {
        $officeId = (int) $user->office_id;
        $from = $from ?? now()->startOfMonth();
        $to = $to ?? now()->endOfMonth();

        $deals = CrmDeal::where('office_id', $officeId)
            ->whereBetween('created_at', [$from, $to])
            ->get(['source', 'stage', 'value']);

        return $deals->groupBy(fn ($d) => $d->source ?: 'unknown')->map(function ($group, $source) {
            $leads = $group->count();
            $won = $group->where('stage', 'closed_won');
            $revenue = (int) $won->sum('value');
            $dealsWon = $won->count();

            return [
                'source' => $source,
                'leads' => $leads,
                'deals' => $dealsWon,
                'revenue' => $revenue,
                'conversion_rate' => $leads > 0 ? round($dealsWon / $leads * 100, 1) : 0,
                'avg_deal_value' => $dealsWon > 0 ? (int) round($revenue / $dealsWon) : 0,
                // Cost/ROI: campaigns budget can extend later — keep null when unknown
                'cost' => null,
                'roi' => null,
            ];
        })->sortByDesc('revenue')->values()->all();
    }

    public function propertyDemandScore(Property $property): array
    {
        $matches = 0;
        $hot = 0;
        try {
            $agent = User::where('office_id', $property->office_id)->where('is_active', true)->first();
            if ($agent) {
                $rev = app(PropertyMatchingService::class)->reverseMatchForProperty($agent, $property, 50);
                $matches = $rev['total'] ?? 0;
                $hot = $rev['summary']['hot'] ?? 0;
            }
        } catch (\Throwable) {
        }

        $viewings = PropertyVisit::where('property_id', $property->id)->count();
        $offers = Schema::hasTable('crm_offers')
            ? CrmOffer::where('property_id', $property->id)->count()
            : 0;
        $presentations = Schema::hasTable('crm_property_presentations')
            ? CrmPropertyPresentation::where('property_id', $property->id)->count()
            : 0;
        $feedback = Schema::hasTable('crm_property_feedback')
            ? CrmPropertyFeedback::where('property_id', $property->id)->count()
            : 0;

        $score = 0;
        $score += min(30, $matches * 2);
        $score += min(20, $hot * 5);
        $score += min(15, $viewings * 3);
        $score += min(20, $offers * 8);
        $score += min(10, $presentations * 2);
        $score += min(5, $feedback);
        $score = (int) max(0, min(100, $score));

        $listed = $property->listed_at ?? $property->created_at ?? now();
        $dom = (int) Carbon::parse($listed)->diffInDays(now());

        $health = match (true) {
            $score >= 70 => 'high_demand',
            $score >= 45 => 'healthy',
            $score >= 25 && $dom < 60 => 'low_interest',
            $dom >= 90 || ($score < 25 && $dom >= 60) => 'critical',
            $dom >= 60 => 'stale',
            default => 'low_interest',
        };

        return [
            'demand_score' => $score,
            'health_status' => $health,
            'days_on_market' => $dom,
            'matched_customers' => $matches,
            'hot_customers' => $hot,
            'viewings' => $viewings,
            'offers' => $offers,
            'presentations' => $presentations,
            'feedback' => $feedback,
        ];
    }

    public function topProperties(User $user, int $limit = 8): array
    {
        $props = Property::where('office_id', $user->office_id)
            ->where('status', PropertyStatus::Active)
            ->orderByDesc('updated_at')
            ->limit(40)
            ->get();

        return $props->map(function (Property $p) {
            $intel = $this->propertyDemandScore($p);
            // persist lightly if columns exist
            try {
                if (Schema::hasColumn('properties', 'demand_score')) {
                    $p->forceFill([
                        'demand_score' => $intel['demand_score'],
                        'health_status' => $intel['health_status'],
                    ])->saveQuietly();
                }
            } catch (\Throwable) {
            }

            return array_merge([
                'id' => $p->id,
                'code' => $p->code,
                'title' => $p->title,
                'price' => $p->price,
                'city' => $p->city,
            ], $intel);
        })->sortByDesc('demand_score')->take($limit)->values()->all();
    }

    public function nextBestActionsForDeal(User $user, CrmDeal $deal): array
    {
        // Extend Phase 1 NBA with more intelligence
        $actions = app(CrmService::class)->nextBestActions($user, $deal);
        if ($deal->lead_score >= 80 && (! $deal->last_contacted_at || $deal->last_contacted_at->lt(now()->subHours(36)))
            && ! in_array($deal->stage, ['closed_won', 'closed_lost'], true)) {
            array_unshift($actions, [
                'code' => 'call_hot_lead',
                'priority' => 'urgent',
                'action' => 'CALL',
                'message' => 'سرنخ داغ بدون تماس کافی — همین امروز تماس بگیرید.',
                'reason' => 'Hot lead + no recent contact',
            ]);
        }
        if ($deal->stage === 'negotiation' && $deal->updated_at?->lt(now()->subDays(5))) {
            $actions[] = [
                'code' => 'unstall_negotiation',
                'priority' => 'high',
                'action' => 'NEGOTIATE',
                'message' => 'مذاکره بیش از ۵ روز بدون به‌روزرسانی است.',
                'reason' => 'Stalled negotiation',
            ];
        }

        return $actions;
    }

    public function dataQuality(User $user): array
    {
        $officeId = (int) $user->office_id;
        $customers = Customer::where('office_id', $officeId);
        $totalCustomers = (clone $customers)->count();
        $missingPhone = (clone $customers)->where(function ($q) {
            $q->whereNull('mobile')->orWhere('mobile', '');
        })->count();
        $deals = CrmDeal::where('office_id', $officeId);
        $missingSource = (clone $deals)->where(function ($q) {
            $q->whereNull('source')->orWhere('source', '');
        })->whereNull('source_id')->count();
        $unassigned = (clone $deals)->whereNull('assigned_to')->count();
        $openStale = (clone $deals)->whereNotIn('stage', ['closed_won', 'closed_lost'])
            ->where('updated_at', '<', now()->subDays(30))->count();

        $issues = [
            ['key' => 'missing_phone', 'label' => 'مشتری بدون موبایل', 'count' => $missingPhone],
            ['key' => 'missing_source', 'label' => 'معامله بدون منبع', 'count' => $missingSource],
            ['key' => 'unassigned', 'label' => 'بدون مسئول', 'count' => $unassigned],
            ['key' => 'stale_open', 'label' => 'معامله باز قدیمی (+۳۰ روز)', 'count' => $openStale],
        ];
        $penalty = array_sum(array_column($issues, 'count'));
        $base = max($totalCustomers + (clone $deals)->count(), 1);
        $health = (int) max(0, min(100, round(100 - ($penalty / $base) * 100)));

        return [
            'health_score' => $health,
            'issues' => $issues,
            'totals' => [
                'customers' => $totalCustomers,
                'deals' => (clone $deals)->count(),
            ],
        ];
    }
}
