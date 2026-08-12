<?php

namespace App\Services\Accounting;

use App\Models\AccountingJournalLine;
use App\Models\AccountingTransaction;
use App\Models\Commission;
use App\Models\CrmDeal;
use App\Models\Customer;
use App\Models\Property;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class AccountingReportService
{
    public function __construct(
        private readonly AccountingBootstrapService $bootstrap,
        private readonly LedgerService $ledger,
    ) {}

    private function assertOffice(User $user): int
    {
        if (! $user->office_id) {
            throw ValidationException::withMessages(['office' => ['دفتر یافت نشد.']]);
        }
        $this->bootstrap->ensureForUser($user);

        return (int) $user->office_id;
    }

    /**
     * Party ledger from journal lines + operational transactions.
     *
     * @return array{party_type:string,party_id:int,name:?string,mobile:?string,debit:int,credit:int,balance:int,transactions:Collection}
     */
    public function peopleLedger(User $user, string $partyType, int $partyId): array
    {
        $officeId = $this->assertOffice($user);

        $name = null;
        $mobile = null;
        if ($partyType === User::class || $partyType === 'user') {
            $partyType = User::class;
            $person = User::where('office_id', $officeId)->findOrFail($partyId);
            if (! $user->canManageOffice() && $user->id !== $person->id) {
                abort(403, 'مشاهده حساب سایر مشاوران مجاز نیست.');
            }
            $name = $person->name;
            $mobile = $person->mobile;
        } elseif ($partyType === Customer::class || $partyType === 'customer') {
            $partyType = Customer::class;
            $person = Customer::where('office_id', $officeId)->findOrFail($partyId);
            $name = $person->name;
            $mobile = $person->mobile;
        }

        $lines = AccountingJournalLine::query()
            ->where('office_id', $officeId)
            ->where('party_type', $partyType)
            ->where('party_id', $partyId)
            ->whereHas('entry', fn ($q) => $q->where('status', 'approved'))
            ->with(['entry:id,entry_number,entry_date,description,source_type', 'account:id,code,name'])
            ->orderByDesc('id')
            ->limit(200)
            ->get();

        $debit = (int) $lines->sum('debit');
        $credit = (int) $lines->sum('credit');

        $txs = AccountingTransaction::query()
            ->where('office_id', $officeId)
            ->where(function ($q) use ($partyType, $partyId) {
                $q->where(function ($inner) use ($partyType, $partyId) {
                    $inner->where('party_type', $partyType)->where('party_id', $partyId);
                })->orWhere(function ($inner) use ($partyType, $partyId) {
                    if ($partyType === User::class) {
                        $inner->where('consultant_id', $partyId);
                    }
                });
            })
            ->where(fn ($q) => $q->whereNull('status')->orWhere('status', 'approved'))
            ->orderByDesc('transaction_date')
            ->limit(100)
            ->get(['id', 'type', 'title', 'amount', 'transaction_date', 'payment_method', 'crm_deal_id', 'property_id']);

        return [
            'party_type' => $partyType,
            'party_id' => $partyId,
            'name' => $name,
            'mobile' => $mobile,
            'debit' => $debit,
            'credit' => $credit,
            // Positive balance = office owes party (creditor), negative = party owes office (debtor)
            'balance' => $credit - $debit,
            'journal_lines' => $lines,
            'transactions' => $txs,
        ];
    }

    public function debtorsCreditors(User $user, string $side = 'debtors'): array
    {
        $officeId = $this->assertOffice($user);
        if (! $user->canManageOffice()) {
            abort(403);
        }

        $rows = AccountingJournalLine::query()
            ->where('office_id', $officeId)
            ->whereNotNull('party_id')
            ->whereHas('entry', fn ($q) => $q->where('status', 'approved'))
            ->selectRaw('party_type, party_id, SUM(debit) as d, SUM(credit) as c, MAX(id) as last_line_id')
            ->groupBy('party_type', 'party_id')
            ->get();

        $items = [];
        foreach ($rows as $row) {
            $balance = (int) $row->c - (int) $row->d;
            // debtors: party owes office => debit > credit => balance negative
            // creditors: office owes party => credit > debit => balance positive
            if ($side === 'debtors' && $balance >= 0) {
                continue;
            }
            if ($side === 'creditors' && $balance <= 0) {
                continue;
            }

            $name = null;
            $mobile = null;
            if ($row->party_type === User::class) {
                $u = User::find($row->party_id);
                $name = $u?->name;
                $mobile = $u?->mobile;
            } elseif ($row->party_type === Customer::class) {
                $c = Customer::find($row->party_id);
                $name = $c?->name;
                $mobile = $c?->mobile;
            }

            $last = AccountingJournalLine::with('entry')->find($row->last_line_id);

            $items[] = [
                'party_type' => $row->party_type,
                'party_id' => (int) $row->party_id,
                'name' => $name,
                'mobile' => $mobile,
                'amount' => abs($balance),
                'balance' => $balance,
                'last_transaction_date' => $last?->entry?->entry_date?->format('Y-m-d'),
            ];
        }

        usort($items, fn ($a, $b) => $b['amount'] <=> $a['amount']);

        return ['side' => $side, 'items' => $items];
    }

    public function consultantsReport(User $user): array
    {
        $officeId = $this->assertOffice($user);

        $query = Commission::where('office_id', $officeId)
            ->when(! $user->canManageOffice(), fn ($q) => $q->where('user_id', $user->id));

        $byUser = (clone $query)
            ->selectRaw('user_id, COUNT(*) as deals_count, SUM(commission_amount) as total_commission, SUM(COALESCE(office_share_amount,0)) as office_share, SUM(COALESCE(consultant_share_amount, commission_amount)) as consultant_share, SUM(CASE WHEN status = "paid" THEN COALESCE(consultant_share_amount, commission_amount) ELSE 0 END) as paid_amount')
            ->groupBy('user_id')
            ->get();

        $rows = [];
        foreach ($byUser as $row) {
            $consultant = User::find($row->user_id);
            $ledger = $this->peopleLedger($user, User::class, (int) $row->user_id);
            $rows[] = [
                'consultant_id' => (int) $row->user_id,
                'name' => $consultant?->name,
                'deals_count' => (int) $row->deals_count,
                'total_commission' => (int) $row->total_commission,
                'office_share' => (int) $row->office_share,
                'consultant_share' => (int) $row->consultant_share,
                'paid_amount' => (int) $row->paid_amount,
                'balance' => (int) $ledger['balance'],
                'avg_commission' => $row->deals_count > 0
                    ? (int) round(((int) $row->total_commission) / (int) $row->deals_count)
                    : 0,
            ];
        }

        return ['consultants' => $rows];
    }

    public function dealFinance(User $user, int $dealId): array
    {
        $officeId = $this->assertOffice($user);
        $deal = CrmDeal::where('office_id', $officeId)->findOrFail($dealId);

        $commissions = Commission::where('office_id', $officeId)
            ->where('crm_deal_id', $deal->id)
            ->get();

        $txs = AccountingTransaction::where('office_id', $officeId)
            ->where('crm_deal_id', $deal->id)
            ->where(fn ($q) => $q->whereNull('status')->orWhere('status', 'approved'))
            ->orderBy('transaction_date')
            ->get();

        $received = (int) $txs->whereIn('type', ['income', 'receipt', 'transfer_in'])->sum('amount');
        $paid = (int) $txs->whereIn('type', ['expense', 'payment', 'transfer_out'])->sum('amount');
        $commissionTotal = (int) $commissions->sum('commission_amount');
        $officeShare = (int) $commissions->sum(fn ($c) => (int) ($c->office_share_amount ?? 0));
        $consultantShare = (int) $commissions->sum(fn ($c) => (int) ($c->consultant_share_amount ?? $c->commission_amount));
        $settled = $commissions->every(fn ($c) => $c->status === 'paid');

        $timeline = [];
        foreach ($commissions as $c) {
            $timeline[] = [
                'at' => $c->created_at?->toIso8601String(),
                'kind' => 'commission',
                'label' => $c->title,
                'amount' => (int) $c->commission_amount,
                'status' => $c->status,
            ];
        }
        foreach ($txs as $t) {
            $timeline[] = [
                'at' => $t->transaction_date?->format('Y-m-d'),
                'kind' => $t->type,
                'label' => $t->title,
                'amount' => (int) $t->amount,
                'status' => $t->status,
            ];
        }
        usort($timeline, fn ($a, $b) => strcmp((string) $a['at'], (string) $b['at']));

        return [
            'deal_id' => $deal->id,
            'deal_title' => $deal->title,
            'deal_value' => (int) ($deal->value ?? 0),
            'commission' => $commissionTotal,
            'office_share' => $officeShare,
            'consultant_share' => $consultantShare,
            'received' => $received,
            'paid' => $paid,
            'balance' => $received - $paid,
            'settlement_status' => $commissions->isEmpty()
                ? 'none'
                : ($settled ? 'settled' : 'open'),
            'commissions' => $commissions,
            'transactions' => $txs,
            'timeline' => $timeline,
        ];
    }

    public function propertyFinance(User $user, int $propertyId): array
    {
        $officeId = $this->assertOffice($user);
        $property = Property::where('office_id', $officeId)->findOrFail($propertyId);

        $deal = CrmDeal::where('office_id', $officeId)
            ->where('property_id', $property->id)
            ->orderByDesc('id')
            ->first();

        if (! $deal) {
            return [
                'property_id' => $property->id,
                'has_deal' => false,
                'status_label' => 'بدون معامله',
            ];
        }

        $finance = $this->dealFinance($user, $deal->id);

        return [
            'property_id' => $property->id,
            'has_deal' => true,
            'status_label' => $finance['settlement_status'] === 'settled' ? 'تسویه‌شده' : 'دارای معامله',
            'deal' => $finance,
        ];
    }

    /**
     * Last 6 Gregorian months trend (labels shown Jalali on frontend).
     *
     * @return array{months: list<array{key:string,label:string,income:int,expense:int,profit:int}>}
     */
    public function monthlyTrend(User $user): array
    {
        $officeId = $this->assertOffice($user);
        $months = [];

        for ($i = 5; $i >= 0; $i--) {
            $start = now()->startOfMonth()->subMonths($i);
            $end = (clone $start)->endOfMonth();
            $income = (int) AccountingTransaction::where('office_id', $officeId)
                ->where('type', 'income')
                ->where(fn ($q) => $q->whereNull('status')->orWhere('status', 'approved'))
                ->whereBetween('transaction_date', [$start->toDateString(), $end->toDateString()])
                ->sum('amount');
            $expense = (int) AccountingTransaction::where('office_id', $officeId)
                ->where('type', 'expense')
                ->where(fn ($q) => $q->whereNull('status')->orWhere('status', 'approved'))
                ->whereBetween('transaction_date', [$start->toDateString(), $end->toDateString()])
                ->sum('amount');

            $months[] = [
                'key' => $start->format('Y-m'),
                'label' => \Morilog\Jalali\Jalalian::fromDateTime($start)->format('%B %Y'),
                'income' => $income,
                'expense' => $expense,
                'profit' => $income - $expense,
            ];
        }

        return ['months' => $months];
    }
}
