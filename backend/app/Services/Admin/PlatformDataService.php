<?php

namespace App\Services\Admin;

use App\Models\AccountingTransaction;
use App\Models\Commission;
use App\Models\Contract;
use App\Models\CrmDeal;
use App\Models\Customer;
use App\Models\Device;
use App\Models\ImpersonationSession;
use App\Models\Office;
use App\Models\OfficeVisitRequest;
use App\Models\Owner;
use App\Models\Payment;
use App\Models\Property;
use App\Models\PropertyVisit;
use App\Models\Subscription;
use App\Models\User;
use App\Models\Wallet;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PlatformDataService
{
    /** @return array<string, mixed> */
    public function overview(): array
    {
        $now = now();
        $monthStart = $now->copy()->startOfMonth();

        return [
            'counts' => [
                'offices' => Office::count(),
                'active_offices' => Office::where('is_active', true)->count(),
                'users' => User::count(),
                'customers' => Customer::count(),
                'owners' => Owner::count(),
                'properties' => Property::count(),
                'crm_deals' => CrmDeal::count(),
                'contracts' => Contract::count(),
                'commissions' => Commission::count(),
                'devices' => Device::count(),
            ],
            'revenue' => [
                'total' => (int) Payment::where('status', 'paid')->sum('amount'),
                'monthly' => (int) Payment::where('status', 'paid')->where('paid_at', '>=', $monthStart)->sum('amount'),
                'paid_count' => Payment::where('status', 'paid')->count(),
                'by_gateway' => Payment::where('status', 'paid')
                    ->select('gateway', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'))
                    ->groupBy('gateway')
                    ->get(),
            ],
            'subscriptions' => [
                'active' => Subscription::where('status', 'active')->count(),
                'expired' => Subscription::where('status', 'expired')->count(),
                'trial_offices' => Office::whereNotNull('trial_ends_at')->where('trial_ends_at', '>', $now)->count(),
            ],
            'wallets' => [
                'total_balance' => (int) Wallet::sum('balance'),
            ],
            'churn_risk' => $this->churnRiskOffices(5),
        ];
    }

    /** @return list<array<string, mixed>> */
    public function churnRiskOffices(int $limit = 10): array
    {
        return Office::with('subscription.plan')
            ->where('is_active', true)
            ->where(function ($q) {
                $q->where('plan_active', false)
                    ->orWhereHas('subscription', fn ($s) => $s->where('status', 'expired'))
                    ->orWhere(function ($q2) {
                        $q2->whereNotNull('trial_ends_at')
                            ->where('trial_ends_at', '<', now()->addDays(3));
                    });
            })
            ->limit($limit)
            ->get(['id', 'name', 'slug', 'plan_active', 'trial_ends_at', 'is_active'])
            ->map(fn (Office $o) => [
                'id' => $o->id,
                'name' => $o->name,
                'slug' => $o->slug,
                'plan_active' => $o->plan_active,
                'trial_ends_at' => $o->trial_ends_at?->toIso8601String(),
                'subscription_status' => $o->subscription?->status,
                'plan_name' => $o->subscription?->plan?->name,
            ])
            ->all();
    }

    /** @return array<string, mixed> */
    public function revenueDetail(): array
    {
        $months = collect(range(5, 0))->map(function (int $i) {
            $start = now()->subMonths($i)->startOfMonth();
            $end = $start->copy()->endOfMonth();

            return [
                'month' => $start->format('Y-m'),
                'label' => $start->locale('fa')->translatedFormat('F Y'),
                'revenue' => (int) Payment::where('status', 'paid')
                    ->whereBetween('paid_at', [$start, $end])
                    ->sum('amount'),
                'count' => Payment::where('status', 'paid')
                    ->whereBetween('paid_at', [$start, $end])
                    ->count(),
            ];
        });

        return [
            'monthly' => $months->all(),
            'mrr_estimate' => (int) Payment::where('status', 'paid')
                ->where('paid_at', '>=', now()->subDays(30))
                ->sum('amount'),
            'avg_payment' => (int) (Payment::where('status', 'paid')->avg('amount') ?? 0),
        ];
    }
}
