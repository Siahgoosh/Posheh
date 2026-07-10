<?php

namespace App\Services\Report;

use App\Models\AccountingTransaction;
use App\Models\CrmDeal;
use App\Models\Property;
use App\Models\User;
use App\Enums\PropertyStatus;

class ReportService
{
    public function officeDashboard(User $user): array
    {
        $officeId = $user->office_id;

        $properties = Property::where('office_id', $officeId);
        $deals = CrmDeal::where('office_id', $officeId);

        return [
            'properties' => [
                'total' => $properties->count(),
                'active' => (clone $properties)->where('status', PropertyStatus::Active->value)->count(),
                'sold' => (clone $properties)->where('status', PropertyStatus::Sold->value)->count(),
                'rented' => (clone $properties)->where('status', PropertyStatus::Rented->value)->count(),
            ],
            'crm' => [
                'total_deals' => $deals->count(),
                'open_deals' => (clone $deals)->whereNotIn('stage', ['closed_won', 'closed_lost'])->count(),
                'won_value' => (int) (clone $deals)->where('stage', 'closed_won')->sum('value'),
            ],
            'accounting' => [
                'month_income' => (int) AccountingTransaction::where('office_id', $officeId)
                    ->where('type', 'income')->where('transaction_date', '>=', now()->startOfMonth())->sum('amount'),
                'month_expense' => (int) AccountingTransaction::where('office_id', $officeId)
                    ->where('type', 'expense')->where('transaction_date', '>=', now()->startOfMonth())->sum('amount'),
            ],
            'by_type' => Property::where('office_id', $officeId)
                ->selectRaw('type, count(*) as count')
                ->groupBy('type')
                ->pluck('count', 'type'),
        ];
    }
}
