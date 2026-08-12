<?php

namespace App\Services\Accounting;

use App\Models\AccountingAccount;
use App\Models\AccountingAuditLog;
use App\Models\AccountingCashAccount;
use App\Models\AccountingCheque;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ChequeService
{
    public function __construct(
        private readonly AccountingBootstrapService $bootstrap,
        private readonly LedgerService $ledger,
    ) {}

    public function list(User $user, array $filters = [])
    {
        $this->bootstrap->ensureForUser($user);

        return AccountingCheque::where('office_id', $user->office_id)
            ->when($filters['direction'] ?? null, fn ($q, $d) => $q->where('direction', $d))
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when($filters['due_from'] ?? null, fn ($q, $d) => $q->whereDate('due_date', '>=', $d))
            ->when($filters['due_to'] ?? null, fn ($q, $d) => $q->whereDate('due_date', '<=', $d))
            ->orderBy('due_date')
            ->paginate(40);
    }

    public function create(User $user, array $data): AccountingCheque
    {
        $this->bootstrap->ensureForUser($user);
        $officeId = (int) $user->office_id;
        $direction = $data['direction']; // in|out
        $amount = (int) $data['amount'];

        return DB::transaction(function () use ($user, $officeId, $data, $direction, $amount) {
            $cheque = AccountingCheque::create([
                'office_id' => $officeId,
                'direction' => $direction,
                'cheque_number' => $data['cheque_number'],
                'sayad_id' => $data['sayad_id'] ?? null,
                'amount' => $amount,
                'bank_name' => $data['bank_name'] ?? null,
                'branch_name' => $data['branch_name'] ?? null,
                'issuer_name' => $data['issuer_name'] ?? null,
                'issue_date' => $data['issue_date'] ?? null,
                'due_date' => $data['due_date'],
                'status' => $direction === 'in' ? 'received' : 'issued',
                'party_type' => $data['party_type'] ?? null,
                'party_id' => $data['party_id'] ?? null,
                'property_id' => $data['property_id'] ?? null,
                'crm_deal_id' => $data['crm_deal_id'] ?? null,
                'cash_account_id' => $data['cash_account_id'] ?? null,
                'description' => $data['description'] ?? null,
                'created_by' => $user->id,
            ]);

            $chequeAsset = AccountingAccount::where('office_id', $officeId)->where('code', '1202')->first();
            $chequeLiability = AccountingAccount::where('office_id', $officeId)->where('code', '2103')->first();
            $receivable = AccountingAccount::where('office_id', $officeId)->where('code', '1201')->first();
            $payable = AccountingAccount::where('office_id', $officeId)->where('code', '2101')->first();

            if ($direction === 'in' && $chequeAsset && $receivable) {
                // Debit cheques receivable, Credit accounts receivable (or income clearing)
                $entry = $this->ledger->post($user, 'cheque', $cheque->id, 'چک دریافتی '.$cheque->cheque_number, [
                    ['account_id' => $chequeAsset->id, 'debit' => $amount, 'credit' => 0],
                    ['account_id' => $receivable->id, 'debit' => 0, 'credit' => $amount],
                ], $cheque->due_date?->toDateString());
                $cheque->update(['journal_entry_id' => $entry->id]);
            } elseif ($direction === 'out' && $chequeLiability && $payable) {
                $entry = $this->ledger->post($user, 'cheque', $cheque->id, 'چک پرداختی '.$cheque->cheque_number, [
                    ['account_id' => $payable->id, 'debit' => $amount, 'credit' => 0],
                    ['account_id' => $chequeLiability->id, 'debit' => 0, 'credit' => $amount],
                ], $cheque->due_date?->toDateString());
                $cheque->update(['journal_entry_id' => $entry->id]);
            }

            AccountingAuditLog::create([
                'office_id' => $officeId,
                'user_id' => $user->id,
                'action' => 'cheque.created',
                'auditable_type' => AccountingCheque::class,
                'auditable_id' => $cheque->id,
                'after' => $cheque->only(['direction', 'amount', 'cheque_number', 'due_date', 'status']),
                'ip' => request()?->ip(),
            ]);

            return $cheque->fresh();
        });
    }

    public function updateStatus(User $user, int $id, string $status): AccountingCheque
    {
        $this->bootstrap->ensureForUser($user);
        if (! $user->canManageOffice()) {
            throw ValidationException::withMessages(['auth' => ['فقط مدیر می‌تواند وضعیت چک را تغییر دهد.']]);
        }
        $cheque = AccountingCheque::where('office_id', $user->office_id)->findOrFail($id);
        $allowed = ['received', 'pending', 'cleared', 'bounced', 'returned', 'spent', 'cancelled', 'issued'];
        if (! in_array($status, $allowed, true)) {
            throw ValidationException::withMessages(['status' => ['وضعیت نامعتبر است.']]);
        }

        return DB::transaction(function () use ($user, $cheque, $status) {
            $before = $cheque->status;
            $cheque->update(['status' => $status]);

            if ($status === 'cleared' && $cheque->direction === 'in') {
                $chequeAsset = AccountingAccount::where('office_id', $cheque->office_id)->where('code', '1202')->first();
                $cash = null;
                if ($cheque->cash_account_id) {
                    $cash = AccountingCashAccount::where('office_id', $cheque->office_id)->find($cheque->cash_account_id);
                }
                if (! $cash) {
                    $cash = AccountingCashAccount::where('office_id', $cheque->office_id)->where('kind', 'bank')->where('is_active', true)->first()
                        ?: AccountingCashAccount::where('office_id', $cheque->office_id)->where('kind', 'cashbox')->first();
                }
                if ($chequeAsset && $cash?->ledger_account_id) {
                    $this->ledger->post($user, 'cheque_clear', $cheque->id, 'وصول چک '.$cheque->cheque_number, [
                        ['account_id' => $cash->ledger_account_id, 'debit' => (int) $cheque->amount, 'credit' => 0],
                        ['account_id' => $chequeAsset->id, 'debit' => 0, 'credit' => (int) $cheque->amount],
                    ], now()->toDateString());
                }
            }

            if ($status === 'bounced' && $cheque->journal_entry_id) {
                $entry = $cheque->journalEntry;
                if ($entry && $entry->status === 'approved') {
                    $this->ledger->reverse($user, $entry, 'cheque bounced');
                }
            }

            AccountingAuditLog::create([
                'office_id' => $cheque->office_id,
                'user_id' => $user->id,
                'action' => 'cheque.status',
                'auditable_type' => AccountingCheque::class,
                'auditable_id' => $cheque->id,
                'before' => ['status' => $before],
                'after' => ['status' => $status],
                'ip' => request()?->ip(),
            ]);

            return $cheque->fresh();
        });
    }

    public function dueAlerts(User $user): array
    {
        $this->bootstrap->ensureForUser($user);
        $officeId = (int) $user->office_id;
        $q = AccountingCheque::where('office_id', $officeId)
            ->whereIn('status', ['received', 'pending', 'issued']);

        return [
            'overdue' => (clone $q)->whereDate('due_date', '<', now()->toDateString())->count(),
            'today' => (clone $q)->whereDate('due_date', now()->toDateString())->count(),
            'tomorrow' => (clone $q)->whereDate('due_date', now()->addDay()->toDateString())->count(),
            'next_3_days' => (clone $q)->whereBetween('due_date', [now()->toDateString(), now()->addDays(3)->toDateString()])->count(),
            'next_7_days' => (clone $q)->whereBetween('due_date', [now()->toDateString(), now()->addDays(7)->toDateString()])->count(),
            'items' => (clone $q)->whereDate('due_date', '<=', now()->addDays(7)->toDateString())
                ->orderBy('due_date')->limit(30)->get(),
        ];
    }
}
