<?php

namespace App\Services\Accounting;

use App\Models\AccountingTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AccountingService
{
    public function list(User $user, ?string $type = null)
    {
        return AccountingTransaction::with(['creator', 'property'])
            ->where('office_id', $user->office_id)
            ->when($type, fn ($q) => $q->where('type', $type))
            ->orderByDesc('transaction_date')
            ->paginate(30);
    }

    public function create(User $user, array $data): AccountingTransaction
    {
        return AccountingTransaction::create(array_merge($data, [
            'office_id' => $user->office_id,
            'created_by' => $user->id,
        ]));
    }

    public function summary(User $user): array
    {
        $officeId = $user->office_id;
        $monthStart = now()->startOfMonth();

        $income = AccountingTransaction::where('office_id', $officeId)
            ->where('type', 'income')
            ->where('transaction_date', '>=', $monthStart)
            ->sum('amount');

        $expense = AccountingTransaction::where('office_id', $officeId)
            ->where('type', 'expense')
            ->where('transaction_date', '>=', $monthStart)
            ->sum('amount');

        return [
            'month_income' => (int) $income,
            'month_expense' => (int) $expense,
            'month_balance' => (int) $income - (int) $expense,
            'total_income' => (int) AccountingTransaction::where('office_id', $officeId)->where('type', 'income')->sum('amount'),
            'total_expense' => (int) AccountingTransaction::where('office_id', $officeId)->where('type', 'expense')->sum('amount'),
        ];
    }
}
