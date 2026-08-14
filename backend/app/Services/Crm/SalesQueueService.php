<?php

namespace App\Services\Crm;

use App\Models\CrmDeal;
use App\Models\CrmFollowUp;
use App\Models\CrmNegotiation;
use App\Models\CrmOffer;
use App\Models\PropertyVisit;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

class SalesQueueService
{
    public function __construct(
        private readonly CrmBootstrapService $bootstrap,
    ) {}

    public function todayQueue(User $user): array
    {
        $this->bootstrap->ensureForUser($user);
        $officeId = (int) $user->office_id;
        $isManager = $user->canManageOffice();

        $followUps = [];
        if (Schema::hasTable('crm_follow_ups')) {
            $followUps = CrmFollowUp::with(['deal', 'customer', 'assignee'])
                ->where('office_id', $officeId)
                ->when(! $isManager, fn ($q) => $q->where('assigned_to', $user->id))
                ->whereIn('status', ['pending', 'overdue'])
                ->whereDate('due_at', '<=', now()->toDateString())
                ->orderBy('due_at')
                ->limit(50)
                ->get()
                ->map(fn (CrmFollowUp $f) => [
                    'kind' => 'follow_up',
                    'priority' => $f->due_at->isPast() ? 'overdue' : ($f->priority === 'urgent' ? 'hot' : 'normal'),
                    'at' => $f->due_at?->toIso8601String(),
                    'title' => $f->notes ?: 'پیگیری',
                    'deal_id' => $f->crm_deal_id,
                    'customer_id' => $f->customer_id,
                    'customer_name' => $f->customer?->name ?? $f->deal?->contact_name,
                    'score' => $f->deal?->lead_score,
                    'id' => $f->id,
                ])->all();
        }

        // Fallback: deals with follow_up_at today/overdue
        $dealFollowUps = CrmDeal::with(['customer', 'assignee'])
            ->where('office_id', $officeId)
            ->when(! $isManager, fn ($q) => $q->where('assigned_to', $user->id))
            ->whereNotNull('follow_up_at')
            ->where('follow_up_at', '<=', now()->endOfDay())
            ->whereNotIn('stage', ['closed_won', 'closed_lost'])
            ->orderBy('follow_up_at')
            ->limit(40)
            ->get()
            ->map(fn (CrmDeal $d) => [
                'kind' => 'deal_follow_up',
                'priority' => $d->follow_up_at->isPast() ? 'overdue' : ($d->lead_score >= 80 ? 'hot' : 'normal'),
                'at' => $d->follow_up_at?->toIso8601String(),
                'title' => 'پیگیری معامله: '.$d->title,
                'deal_id' => $d->id,
                'customer_id' => $d->customer_id,
                'customer_name' => $d->customer?->name ?? $d->contact_name,
                'score' => $d->lead_score,
                'id' => $d->id,
            ])->all();

        $visits = PropertyVisit::with(['customer', 'property'])
            ->where('office_id', $officeId)
            ->when(! $isManager, fn ($q) => $q->where('assigned_to', $user->id))
            ->where('status', 'scheduled')
            ->whereDate('visit_at', now()->toDateString())
            ->orderBy('visit_at')
            ->get()
            ->map(fn (PropertyVisit $v) => [
                'kind' => 'visit',
                'priority' => 'hot',
                'at' => $v->visit_at?->toIso8601String(),
                'title' => 'بازدید '.($v->property?->code ?? ''),
                'deal_id' => $v->crm_deal_id,
                'customer_id' => $v->customer_id,
                'customer_name' => $v->customer?->name,
                'score' => null,
                'id' => $v->id,
            ])->all();

        $negotiations = [];
        if (Schema::hasTable('crm_negotiations')) {
            $negotiations = CrmNegotiation::with(['customer', 'property'])
                ->where('office_id', $officeId)
                ->when(! $isManager, fn ($q) => $q->where('agent_id', $user->id))
                ->whereIn('status', ['open', 'stalled'])
                ->orderByDesc('updated_at')
                ->limit(20)
                ->get()
                ->map(fn (CrmNegotiation $n) => [
                    'kind' => 'negotiation',
                    'priority' => $n->status === 'stalled' ? 'overdue' : 'normal',
                    'at' => $n->updated_at?->toIso8601String(),
                    'title' => 'مذاکره '.($n->property?->code ?? ''),
                    'deal_id' => $n->crm_deal_id,
                    'customer_id' => $n->customer_id,
                    'customer_name' => $n->customer?->name ?? $n->buyer_name,
                    'score' => null,
                    'id' => $n->id,
                ])->all();
        }

        $items = collect([...$followUps, ...$dealFollowUps, ...$visits, ...$negotiations])
            ->sortBy('at')
            ->values()
            ->all();

        $hotLeads = CrmDeal::where('office_id', $officeId)
            ->when(! $isManager, fn ($q) => $q->where('assigned_to', $user->id))
            ->whereNotIn('stage', ['closed_won', 'closed_lost'])
            ->where('lead_score', '>=', 80)
            ->orderByDesc('lead_score')
            ->limit(10)
            ->get(['id', 'title', 'contact_name', 'lead_score', 'follow_up_at', 'stage']);

        return [
            'date' => now()->toDateString(),
            'summary' => [
                'hot_leads' => $hotLeads->count(),
                'follow_ups' => count($followUps) + count($dealFollowUps),
                'visits' => count($visits),
                'negotiations' => count($negotiations),
                'overdue' => collect($items)->where('priority', 'overdue')->count(),
            ],
            'hot_leads' => $hotLeads,
            'queue' => $items,
        ];
    }

    public function opportunities(User $user): array
    {
        $officeId = (int) $user->office_id;
        $isManager = $user->canManageOffice();

        $hot = CrmDeal::where('office_id', $officeId)
            ->when(! $isManager, fn ($q) => $q->where('assigned_to', $user->id))
            ->where('lead_score', '>=', 80)
            ->whereNotIn('stage', ['closed_won', 'closed_lost'])
            ->orderByDesc('lead_score')
            ->limit(15)
            ->get();

        $overdue = CrmDeal::where('office_id', $officeId)
            ->when(! $isManager, fn ($q) => $q->where('assigned_to', $user->id))
            ->whereNotNull('follow_up_at')
            ->where('follow_up_at', '<', now())
            ->whereNotIn('stage', ['closed_won', 'closed_lost'])
            ->orderBy('follow_up_at')
            ->limit(15)
            ->get();

        $stalled = Schema::hasTable('crm_negotiations')
            ? CrmNegotiation::where('office_id', $officeId)->where('status', 'stalled')->limit(15)->get()
            : collect();

        $pendingOffers = Schema::hasTable('crm_offers')
            ? CrmOffer::where('office_id', $officeId)->whereIn('status', ['submitted', 'countered'])->limit(15)->get()
            : collect();

        $dormant = \App\Models\Customer::where('office_id', $officeId)
            ->where(function ($q) {
                $q->where('lifecycle', 'dormant')
                    ->orWhere(function ($q2) {
                        $q2->whereNotNull('last_contacted_at')
                            ->where('last_contacted_at', '<', now()->subDays(90));
                    });
            })
            ->limit(15)
            ->get(['id', 'name', 'mobile', 'last_contacted_at', 'lifecycle']);

        return [
            'hot_leads' => $hot,
            'overdue_follow_ups' => $overdue,
            'stalled_negotiations' => $stalled,
            'pending_offers' => $pendingOffers,
            'reactivation' => $dormant,
        ];
    }

    public function dailyBriefing(User $user): array
    {
        $queue = $this->todayQueue($user);

        return [
            'message' => sprintf(
                'امروز: %d سرنخ داغ · %d پیگیری · %d بازدید · %d مذاکره · %d معوق',
                $queue['summary']['hot_leads'],
                $queue['summary']['follow_ups'],
                $queue['summary']['visits'],
                $queue['summary']['negotiations'],
                $queue['summary']['overdue'],
            ),
            'summary' => $queue['summary'],
            'generated_at' => Carbon::now()->toIso8601String(),
        ];
    }
}
