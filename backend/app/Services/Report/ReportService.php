<?php

namespace App\Services\Report;

use App\Models\AccountingTransaction;
use App\Models\Commission;
use App\Models\CrmDeal;
use App\Models\Property;
use App\Models\PropertyVisit;
use App\Models\User;
use App\Enums\PropertyStatus;

class ReportService
{
    public function officeDashboard(User $user): array
    {
        $officeId = $user->office_id;

        $properties = Property::where('office_id', $officeId);
        $deals = CrmDeal::where('office_id', $officeId);

        $totalDeals = $deals->count();
        $wonDeals = (clone $deals)->where('stage', 'closed_won')->count();

        return [
            'properties' => [
                'total' => $properties->count(),
                'active' => (clone $properties)->where('status', PropertyStatus::Active->value)->count(),
                'sold' => (clone $properties)->where('status', PropertyStatus::Sold->value)->count(),
                'rented' => (clone $properties)->where('status', PropertyStatus::Rented->value)->count(),
                'expiring_soon' => (clone $properties)->where('status', PropertyStatus::Active->value)
                    ->whereNotNull('expires_at')
                    ->whereBetween('expires_at', [now(), now()->addDays(7)])
                    ->count(),
            ],
            'crm' => [
                'total_deals' => $totalDeals,
                'open_deals' => (clone $deals)->whereNotIn('stage', ['closed_won', 'closed_lost'])->count(),
                'won_value' => (int) (clone $deals)->where('stage', 'closed_won')->sum('value'),
                'conversion_rate' => $totalDeals > 0 ? round($wonDeals / $totalDeals * 100, 1) : 0,
                'pipeline' => $this->pipelineStages($officeId),
            ],
            'accounting' => [
                'month_income' => (int) AccountingTransaction::where('office_id', $officeId)
                    ->where('type', 'income')->where('transaction_date', '>=', now()->startOfMonth())->sum('amount'),
                'month_expense' => (int) AccountingTransaction::where('office_id', $officeId)
                    ->where('type', 'expense')->where('transaction_date', '>=', now()->startOfMonth())->sum('amount'),
                'monthly_trend' => $this->monthlyTrend($officeId),
            ],
            'commissions' => [
                'pending' => (int) Commission::where('office_id', $officeId)->where('status', 'pending')->sum('commission_amount'),
                'paid_month' => (int) Commission::where('office_id', $officeId)->where('status', 'paid')
                    ->where('paid_at', '>=', now()->startOfMonth())->sum('commission_amount'),
            ],
            'visits' => [
                'this_week' => PropertyVisit::where('office_id', $officeId)
                    ->where('status', 'scheduled')
                    ->whereBetween('visit_at', [now()->startOfWeek(), now()->endOfWeek()])
                    ->count(),
            ],
            'consultants' => $this->consultantKpi($officeId),
            'by_type' => Property::where('office_id', $officeId)
                ->selectRaw('type, count(*) as count')
                ->groupBy('type')
                ->pluck('count', 'type'),
        ];
    }

    private function pipelineStages(int $officeId): array
    {
        $stages = ['lead', 'contact', 'visit', 'negotiation', 'closed_won', 'closed_lost'];
        $labels = [
            'lead' => 'سرنخ', 'contact' => 'تماس', 'visit' => 'بازدید',
            'negotiation' => 'مذاکره', 'closed_won' => 'موفق', 'closed_lost' => 'ناموفق',
        ];

        $rows = CrmDeal::where('office_id', $officeId)
            ->selectRaw('stage, count(*) as count, coalesce(sum(value), 0) as total_value')
            ->groupBy('stage')
            ->get()
            ->keyBy('stage');

        return collect($stages)->map(fn ($stage) => [
            'stage' => $stage,
            'label' => $labels[$stage],
            'count' => (int) ($rows[$stage]->count ?? 0),
            'value' => (int) ($rows[$stage]->total_value ?? 0),
        ])->values()->all();
    }

    private function monthlyTrend(int $officeId): array
    {
        $months = [];
        for ($i = 5; $i >= 0; $i--) {
            $start = now()->subMonths($i)->startOfMonth();
            $end = (clone $start)->endOfMonth();
            $months[] = [
                'month' => $start->format('Y-m'),
                'label' => \Morilog\Jalali\Jalalian::fromDateTime($start)->format('Y/m'),
                'income' => (int) AccountingTransaction::where('office_id', $officeId)
                    ->where('type', 'income')
                    ->whereBetween('transaction_date', [$start, $end])
                    ->sum('amount'),
                'expense' => (int) AccountingTransaction::where('office_id', $officeId)
                    ->where('type', 'expense')
                    ->whereBetween('transaction_date', [$start, $end])
                    ->sum('amount'),
            ];
        }

        return $months;
    }

    private function consultantKpi(int $officeId): array
    {
        return User::where('office_id', $officeId)
            ->whereIn('role', ['consultant', 'office_manager'])
            ->get()
            ->map(function (User $user) use ($officeId) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'properties' => Property::where('office_id', $officeId)->where('created_by', $user->id)->count(),
                    'deals_won' => CrmDeal::where('office_id', $officeId)->where('assigned_to', $user->id)->where('stage', 'closed_won')->count(),
                    'open_deals' => CrmDeal::where('office_id', $officeId)->where('assigned_to', $user->id)
                        ->whereNotIn('stage', ['closed_won', 'closed_lost'])->count(),
                    'commission_pending' => (int) Commission::where('office_id', $officeId)
                        ->where('user_id', $user->id)->where('status', 'pending')->sum('commission_amount'),
                ];
            })
            ->sortByDesc('deals_won')
            ->values()
            ->all();
    }
}
