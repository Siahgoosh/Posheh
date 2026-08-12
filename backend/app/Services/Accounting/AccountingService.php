<?php

namespace App\Services\Accounting;

use App\Models\AccountingAccount;
use App\Models\AccountingAuditLog;
use App\Models\AccountingCashAccount;
use App\Models\AccountingTransaction;
use App\Models\CrmDeal;
use App\Models\Property;
use App\Models\User;
use App\Services\Subscription\SubscriptionAccessService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AccountingService
{
    public function __construct(
        private readonly AccountingBootstrapService $bootstrap,
        private readonly LedgerService $ledger,
        private readonly SubscriptionAccessService $access,
    ) {}

    private function assertFeature(User $user): void
    {
        if (! $user->office_id) {
            throw ValidationException::withMessages(['office' => ['دفتر یافت نشد.']]);
        }
        $office = $user->office;
        if ($office && ! $this->access->officeHasFeature($office, 'accounting') && ! $user->isSuperAdmin() && ! $user->isPlatformStaff()) {
            throw ValidationException::withMessages(['plan' => ['حسابداری در پلن فعلی فعال نیست.']]);
        }
    }

    private function nextTxNumber(int $officeId): string
    {
        $n = AccountingTransaction::where('office_id', $officeId)->count() + 1;

        return sprintf('TX-%s-%05d', now()->format('Ymd'), $n);
    }

    public function list(User $user, array $filters = []): LengthAwarePaginator
    {
        $this->assertFeature($user);
        $this->bootstrap->ensureForUser($user);

        return AccountingTransaction::with(['creator', 'property', 'account', 'cashAccount'])
            ->where('office_id', $user->office_id)
            ->when($filters['type'] ?? null, fn ($q, $t) => $q->where('type', $t))
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when($filters['from'] ?? null, fn ($q, $d) => $q->whereDate('transaction_date', '>=', $d))
            ->when($filters['to'] ?? null, fn ($q, $d) => $q->whereDate('transaction_date', '<=', $d))
            ->when($filters['q'] ?? null, function ($q, $term) {
                $q->where(function ($inner) use ($term) {
                    $inner->where('title', 'like', "%{$term}%")
                        ->orWhere('reference', 'like', "%{$term}%")
                        ->orWhere('transaction_number', 'like', "%{$term}%");
                });
            })
            ->where(function ($q) {
                $q->whereNull('status')->orWhere('status', '!=', 'void');
            })
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->paginate(min(100, (int) ($filters['per_page'] ?? 30)));
    }

    public function create(User $user, array $data): AccountingTransaction
    {
        $this->assertFeature($user);
        $this->bootstrap->ensureForUser($user);
        $officeId = (int) $user->office_id;

        $type = $data['type']; // income|expense
        $amount = (int) $data['amount'];
        if ($amount < 1) {
            throw ValidationException::withMessages(['amount' => ['مبلغ نامعتبر است.']]);
        }

        if (! empty($data['property_id'])) {
            Property::where('office_id', $officeId)->findOrFail($data['property_id']);
        }
        if (! empty($data['crm_deal_id'])) {
            CrmDeal::where('office_id', $officeId)->findOrFail($data['crm_deal_id']);
        }

        $cashAccount = null;
        if (! empty($data['cash_account_id'])) {
            $cashAccount = AccountingCashAccount::where('office_id', $officeId)->findOrFail($data['cash_account_id']);
        } else {
            $cashAccount = AccountingCashAccount::where('office_id', $officeId)
                ->where('kind', 'cashbox')
                ->where('is_active', true)
                ->orderBy('id')
                ->first();
        }

        $incomeExpenseAccount = null;
        if (! empty($data['account_id'])) {
            $incomeExpenseAccount = AccountingAccount::where('office_id', $officeId)->findOrFail($data['account_id']);
        } else {
            $code = $type === 'income' ? '4199' : '5199';
            if (($data['category'] ?? '') === 'commission' || str_contains((string) ($data['title'] ?? ''), 'کمیسیون')) {
                $code = $type === 'income' ? '4101' : '5201';
            }
            $incomeExpenseAccount = AccountingAccount::where('office_id', $officeId)->where('code', $code)->first();
        }

        $cashLedgerId = $cashAccount?->ledger_account_id
            ?: AccountingAccount::where('office_id', $officeId)->where('code', '1101')->value('id');

        if (! $incomeExpenseAccount || ! $cashLedgerId) {
            throw ValidationException::withMessages(['account' => ['حساب‌های پیش‌فرض آماده نیست. دوباره تلاش کنید.']]);
        }

        return DB::transaction(function () use ($user, $officeId, $data, $type, $amount, $cashAccount, $incomeExpenseAccount, $cashLedgerId) {
            $tx = AccountingTransaction::create([
                'office_id' => $officeId,
                'transaction_number' => $this->nextTxNumber($officeId),
                'created_by' => $user->id,
                'approved_by' => $user->id,
                'property_id' => $data['property_id'] ?? null,
                'crm_deal_id' => $data['crm_deal_id'] ?? null,
                'commission_id' => $data['commission_id'] ?? null,
                'consultant_id' => $data['consultant_id'] ?? null,
                'account_id' => $incomeExpenseAccount->id,
                'cash_account_id' => $cashAccount?->id,
                'pos_terminal_id' => $data['pos_terminal_id'] ?? null,
                'type' => $type,
                'status' => 'approved',
                'category' => $data['category'] ?? null,
                'payment_method' => $data['payment_method'] ?? ($cashAccount?->kind === 'bank' ? 'bank' : 'cash'),
                'amount' => $amount,
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'transaction_date' => $data['transaction_date'],
                'reference' => $data['reference'] ?? null,
                'party_type' => $data['party_type'] ?? null,
                'party_id' => $data['party_id'] ?? null,
            ]);

            $lines = $type === 'income'
                ? [
                    ['account_id' => (int) $cashLedgerId, 'debit' => $amount, 'credit' => 0, 'memo' => $tx->title],
                    ['account_id' => $incomeExpenseAccount->id, 'debit' => 0, 'credit' => $amount, 'memo' => $tx->title],
                ]
                : [
                    ['account_id' => $incomeExpenseAccount->id, 'debit' => $amount, 'credit' => 0, 'memo' => $tx->title],
                    ['account_id' => (int) $cashLedgerId, 'debit' => 0, 'credit' => $amount, 'memo' => $tx->title],
                ];

            $entry = $this->ledger->post(
                $user,
                $type,
                $tx->id,
                $tx->title,
                $lines,
                $tx->transaction_date?->toDateString() ?? $data['transaction_date'],
            );

            $tx->update(['journal_entry_id' => $entry->id]);

            AccountingAuditLog::create([
                'office_id' => $officeId,
                'user_id' => $user->id,
                'action' => 'transaction.created',
                'auditable_type' => AccountingTransaction::class,
                'auditable_id' => $tx->id,
                'after' => $tx->only(['type', 'amount', 'title', 'transaction_number']),
                'ip' => request()?->ip(),
            ]);

            return $tx->fresh(['creator', 'property', 'account', 'cashAccount']);
        });
    }

    public function transfer(User $user, array $data): array
    {
        $this->assertFeature($user);
        if (! $user->canManageOffice()) {
            throw ValidationException::withMessages(['auth' => ['فقط مدیر دفتر می‌تواند انتقال ثبت کند.']]);
        }
        $this->bootstrap->ensureForUser($user);
        $officeId = (int) $user->office_id;
        $amount = (int) $data['amount'];
        $from = AccountingCashAccount::where('office_id', $officeId)->findOrFail($data['from_cash_account_id']);
        $to = AccountingCashAccount::where('office_id', $officeId)->findOrFail($data['to_cash_account_id']);
        if ($from->id === $to->id) {
            throw ValidationException::withMessages(['to_cash_account_id' => ['حساب مبدأ و مقصد یکسان است.']]);
        }
        if (! $from->ledger_account_id || ! $to->ledger_account_id) {
            throw ValidationException::withMessages(['account' => ['حساب دفتر کل برای صندوق/بانک تنظیم نشده.']]);
        }

        return DB::transaction(function () use ($user, $officeId, $data, $amount, $from, $to) {
            $entry = $this->ledger->post(
                $user,
                'transfer',
                null,
                $data['description'] ?? "انتقال از {$from->name} به {$to->name}",
                [
                    ['account_id' => $to->ledger_account_id, 'debit' => $amount, 'credit' => 0],
                    ['account_id' => $from->ledger_account_id, 'debit' => 0, 'credit' => $amount],
                ],
                $data['transaction_date'] ?? now()->toDateString(),
            );

            $out = AccountingTransaction::create([
                'office_id' => $officeId,
                'transaction_number' => $this->nextTxNumber($officeId),
                'created_by' => $user->id,
                'approved_by' => $user->id,
                'cash_account_id' => $from->id,
                'account_id' => $from->ledger_account_id,
                'type' => 'transfer_out',
                'status' => 'approved',
                'payment_method' => 'transfer',
                'amount' => $amount,
                'title' => 'انتقال خروجی به '.$to->name,
                'description' => $data['description'] ?? null,
                'transaction_date' => $data['transaction_date'] ?? now()->toDateString(),
                'journal_entry_id' => $entry->id,
                'reference' => $data['reference'] ?? null,
            ]);

            $in = AccountingTransaction::create([
                'office_id' => $officeId,
                'transaction_number' => $this->nextTxNumber($officeId),
                'created_by' => $user->id,
                'approved_by' => $user->id,
                'cash_account_id' => $to->id,
                'account_id' => $to->ledger_account_id,
                'type' => 'transfer_in',
                'status' => 'approved',
                'payment_method' => 'transfer',
                'amount' => $amount,
                'title' => 'انتقال ورودی از '.$from->name,
                'description' => $data['description'] ?? null,
                'transaction_date' => $data['transaction_date'] ?? now()->toDateString(),
                'journal_entry_id' => $entry->id,
                'reference' => $data['reference'] ?? null,
            ]);

            return ['journal' => $entry, 'out' => $out, 'in' => $in];
        });
    }

    public function void(User $user, int $id, ?string $reason = null): AccountingTransaction
    {
        $this->assertFeature($user);
        if (! $user->canManageOffice()) {
            throw ValidationException::withMessages(['auth' => ['فقط مدیر می‌تواند ابطال کند.']]);
        }
        $tx = AccountingTransaction::where('office_id', $user->office_id)->findOrFail($id);
        if ($tx->status === 'void') {
            return $tx;
        }

        return DB::transaction(function () use ($user, $tx, $reason) {
            if ($tx->journal_entry_id) {
                $entry = $tx->journalEntry;
                if ($entry && $entry->status === 'approved') {
                    $this->ledger->reverse($user, $entry, $reason ?: 'void transaction');
                }
            }
            $before = $tx->only(['status', 'amount', 'title']);
            $tx->update([
                'status' => 'void',
                'voided_at' => now(),
                'voided_by' => $user->id,
                'void_reason' => $reason,
            ]);
            AccountingAuditLog::create([
                'office_id' => $tx->office_id,
                'user_id' => $user->id,
                'action' => 'transaction.voided',
                'auditable_type' => AccountingTransaction::class,
                'auditable_id' => $tx->id,
                'before' => $before,
                'after' => ['status' => 'void', 'reason' => $reason],
                'ip' => request()?->ip(),
            ]);

            return $tx->fresh();
        });
    }

    public function summary(User $user): array
    {
        $this->assertFeature($user);
        $this->bootstrap->ensureForUser($user);
        $officeId = (int) $user->office_id;
        $monthStart = now()->startOfMonth()->toDateString();
        $today = now()->toDateString();

        $base = AccountingTransaction::where('office_id', $officeId)
            ->where(fn ($q) => $q->whereNull('status')->orWhere('status', 'approved'));

        $monthIncome = (int) (clone $base)->where('type', 'income')->where('transaction_date', '>=', $monthStart)->sum('amount');
        $monthExpense = (int) (clone $base)->where('type', 'expense')->where('transaction_date', '>=', $monthStart)->sum('amount');
        $todayIncome = (int) (clone $base)->where('type', 'income')->whereDate('transaction_date', $today)->sum('amount');
        $todayExpense = (int) (clone $base)->where('type', 'expense')->whereDate('transaction_date', $today)->sum('amount');

        $cashAccounts = AccountingCashAccount::where('office_id', $officeId)->where('is_active', true)->get();
        $cashBalance = 0;
        $bankBalance = 0;
        $cashDetails = [];
        foreach ($cashAccounts as $ca) {
            $ledgerBal = $ca->ledger_account_id
                ? $this->ledger->accountBalance($officeId, (int) $ca->ledger_account_id)
                : 0;
            $bal = (int) $ca->opening_balance + $ledgerBal;
            if ($ca->kind === 'cashbox') {
                $cashBalance += $bal;
            } else {
                $bankBalance += $bal;
            }
            $cashDetails[] = [
                'id' => $ca->id,
                'name' => $ca->name,
                'kind' => $ca->kind,
                'balance' => $bal,
            ];
        }

        $consultantPayableAccount = AccountingAccount::where('office_id', $officeId)->where('code', '2102')->first();
        $consultantPayable = $consultantPayableAccount
            ? -1 * $this->ledger->accountBalance($officeId, $consultantPayableAccount->id)
            : 0;

        $receivableAccount = AccountingAccount::where('office_id', $officeId)->where('code', '1201')->first();
        $receivables = $receivableAccount
            ? $this->ledger->accountBalance($officeId, $receivableAccount->id)
            : 0;

        return [
            'month_income' => $monthIncome,
            'month_expense' => $monthExpense,
            'month_balance' => $monthIncome - $monthExpense,
            'month_profit' => $monthIncome - $monthExpense,
            'today_income' => $todayIncome,
            'today_expense' => $todayExpense,
            'total_income' => (int) (clone $base)->where('type', 'income')->sum('amount'),
            'total_expense' => (int) (clone $base)->where('type', 'expense')->sum('amount'),
            'cash_balance' => $cashBalance,
            'bank_balance' => $bankBalance,
            'liquid_assets' => $cashBalance + $bankBalance,
            'receivables' => max(0, $receivables),
            'consultant_payables' => max(0, $consultantPayable),
            'cash_accounts' => $cashDetails,
            'currency' => 'IRT',
            'currency_label' => 'تومان',
        ];
    }

    public function dashboard(User $user): array
    {
        $summary = $this->summary($user);
        $officeId = (int) $user->office_id;

        $chequeDue = \App\Models\AccountingCheque::where('office_id', $officeId)
            ->whereIn('status', ['received', 'pending', 'issued'])
            ->whereDate('due_date', '<=', now()->addDays(7)->toDateString())
            ->orderBy('due_date')
            ->limit(20)
            ->get(['id', 'direction', 'cheque_number', 'amount', 'due_date', 'status', 'issuer_name']);

        $recent = AccountingTransaction::where('office_id', $officeId)
            ->where(fn ($q) => $q->whereNull('status')->orWhere('status', 'approved'))
            ->orderByDesc('transaction_date')
            ->limit(10)
            ->get(['id', 'type', 'title', 'amount', 'transaction_date', 'payment_method']);

        return [
            'summary' => $summary,
            'cheque_alerts' => $chequeDue,
            'recent_transactions' => $recent,
        ];
    }

    public function profitAndLoss(User $user, ?string $from = null, ?string $to = null): array
    {
        $this->assertFeature($user);
        $this->bootstrap->ensureForUser($user);
        $officeId = (int) $user->office_id;
        $from = $from ?: now()->startOfMonth()->toDateString();
        $to = $to ?: now()->toDateString();

        $incomeAccounts = AccountingAccount::where('office_id', $officeId)->where('group_key', 'income')->where('is_active', true)->get();
        $expenseAccounts = AccountingAccount::where('office_id', $officeId)->where('group_key', 'expenses')->where('is_active', true)->get();

        $incomeRows = [];
        $incomeTotal = 0;
        foreach ($incomeAccounts as $acc) {
            $credit = (int) \App\Models\AccountingJournalLine::where('office_id', $officeId)
                ->where('account_id', $acc->id)
                ->whereHas('entry', fn ($q) => $q->where('status', 'approved')->whereBetween('entry_date', [$from, $to]))
                ->sum('credit');
            $debit = (int) \App\Models\AccountingJournalLine::where('office_id', $officeId)
                ->where('account_id', $acc->id)
                ->whereHas('entry', fn ($q) => $q->where('status', 'approved')->whereBetween('entry_date', [$from, $to]))
                ->sum('debit');
            $net = $credit - $debit;
            if ($net === 0) {
                continue;
            }
            $incomeRows[] = ['account' => $acc->name, 'code' => $acc->code, 'amount' => $net];
            $incomeTotal += $net;
        }

        $expenseRows = [];
        $expenseTotal = 0;
        foreach ($expenseAccounts as $acc) {
            $debit = (int) \App\Models\AccountingJournalLine::where('office_id', $officeId)
                ->where('account_id', $acc->id)
                ->whereHas('entry', fn ($q) => $q->where('status', 'approved')->whereBetween('entry_date', [$from, $to]))
                ->sum('debit');
            $credit = (int) \App\Models\AccountingJournalLine::where('office_id', $officeId)
                ->where('account_id', $acc->id)
                ->whereHas('entry', fn ($q) => $q->where('status', 'approved')->whereBetween('entry_date', [$from, $to]))
                ->sum('credit');
            $net = $debit - $credit;
            if ($net === 0) {
                continue;
            }
            $expenseRows[] = ['account' => $acc->name, 'code' => $acc->code, 'amount' => $net];
            $expenseTotal += $net;
        }

        return [
            'from' => $from,
            'to' => $to,
            'income' => $incomeRows,
            'expenses' => $expenseRows,
            'total_income' => $incomeTotal,
            'total_expenses' => $expenseTotal,
            'net_profit' => $incomeTotal - $expenseTotal,
        ];
    }
}
