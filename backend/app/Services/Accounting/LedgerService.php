<?php

namespace App\Services\Accounting;

use App\Models\AccountingAuditLog;
use App\Models\AccountingJournalEntry;
use App\Models\AccountingJournalLine;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LedgerService
{
    public function __construct(
        private readonly AccountingBootstrapService $bootstrap,
    ) {}

    public function nextEntryNumber(int $officeId): string
    {
        $year = now()->format('Y');
        $count = AccountingJournalEntry::where('office_id', $officeId)
            ->whereYear('created_at', (int) $year)
            ->count() + 1;

        return sprintf('JE-%s-%05d', $year, $count);
    }

    /**
     * @param  list<array{account_id:int,debit?:int,credit?:int,memo?:?string,party_type?:?string,party_id?:?int}>  $lines
     */
    public function post(
        User $user,
        string $sourceType,
        ?int $sourceId,
        string $description,
        array $lines,
        ?string $entryDate = null,
        string $status = 'approved',
    ): AccountingJournalEntry {
        $this->bootstrap->ensureForUser($user);
        $officeId = (int) $user->office_id;

        if ($officeId < 1) {
            throw ValidationException::withMessages(['office' => ['دفتر معتبر نیست.']]);
        }

        $normalized = [];
        $debitTotal = 0;
        $creditTotal = 0;

        foreach ($lines as $line) {
            $debit = (int) ($line['debit'] ?? 0);
            $credit = (int) ($line['credit'] ?? 0);
            if ($debit < 0 || $credit < 0) {
                throw ValidationException::withMessages(['lines' => ['مبالغ منفی مجاز نیست.']]);
            }
            if (($debit > 0 && $credit > 0) || ($debit === 0 && $credit === 0)) {
                throw ValidationException::withMessages(['lines' => ['هر ردیف باید فقط بدهکار یا فقط بستانکار باشد.']]);
            }
            $debitTotal += $debit;
            $creditTotal += $credit;
            $normalized[] = $line + ['debit' => $debit, 'credit' => $credit];
        }

        if ($debitTotal !== $creditTotal || $debitTotal < 1) {
            throw ValidationException::withMessages([
                'lines' => ['سند نامتوازن است — جمع بدهکار باید برابر جمع بستانکار و بزرگ‌تر از صفر باشد.'],
            ]);
        }

        return DB::transaction(function () use ($user, $officeId, $sourceType, $sourceId, $description, $normalized, $entryDate, $status, $debitTotal) {
            $entry = AccountingJournalEntry::create([
                'office_id' => $officeId,
                'entry_number' => $this->nextEntryNumber($officeId),
                'entry_date' => $entryDate ?: now()->toDateString(),
                'status' => $status,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'description' => $description,
                'created_by' => $user->id,
                'approved_by' => $status === 'approved' ? $user->id : null,
                'approved_at' => $status === 'approved' ? now() : null,
            ]);

            foreach ($normalized as $line) {
                AccountingJournalLine::create([
                    'office_id' => $officeId,
                    'journal_entry_id' => $entry->id,
                    'account_id' => (int) $line['account_id'],
                    'debit' => (int) $line['debit'],
                    'credit' => (int) $line['credit'],
                    'memo' => $line['memo'] ?? null,
                    'party_type' => $line['party_type'] ?? null,
                    'party_id' => $line['party_id'] ?? null,
                ]);
            }

            AccountingAuditLog::create([
                'office_id' => $officeId,
                'user_id' => $user->id,
                'action' => 'journal.posted',
                'auditable_type' => AccountingJournalEntry::class,
                'auditable_id' => $entry->id,
                'after' => [
                    'entry_number' => $entry->entry_number,
                    'source_type' => $sourceType,
                    'amount' => $debitTotal,
                ],
                'ip' => request()?->ip(),
            ]);

            return $entry->load('lines.account');
        });
    }

    public function reverse(User $user, AccountingJournalEntry $entry, string $reason = 'reversal'): AccountingJournalEntry
    {
        if ($entry->office_id !== $user->office_id && ! $user->isSuperAdmin()) {
            abort(403);
        }
        if ($entry->status === 'reversed' || $entry->status === 'cancelled') {
            throw ValidationException::withMessages(['entry' => ['این سند قبلاً برگشت خورده است.']]);
        }

        $lines = $entry->lines->map(fn ($l) => [
            'account_id' => $l->account_id,
            'debit' => (int) $l->credit,
            'credit' => (int) $l->debit,
            'memo' => 'برگشت: '.($l->memo ?: $entry->entry_number),
            'party_type' => $l->party_type,
            'party_id' => $l->party_id,
        ])->all();

        return DB::transaction(function () use ($user, $entry, $lines, $reason) {
            $reversal = $this->post(
                $user,
                'reversal',
                $entry->id,
                'برگشت سند '.$entry->entry_number.($reason ? " — {$reason}" : ''),
                $lines,
                now()->toDateString(),
                'approved',
            );

            $entry->update([
                'status' => 'reversed',
                'reversed_entry_id' => $reversal->id,
            ]);

            AccountingAuditLog::create([
                'office_id' => $entry->office_id,
                'user_id' => $user->id,
                'action' => 'journal.reversed',
                'auditable_type' => AccountingJournalEntry::class,
                'auditable_id' => $entry->id,
                'after' => ['reversal_id' => $reversal->id, 'reason' => $reason],
                'ip' => request()?->ip(),
            ]);

            return $reversal;
        });
    }

    public function accountBalance(int $officeId, int $accountId): int
    {
        $row = AccountingJournalLine::query()
            ->where('office_id', $officeId)
            ->where('account_id', $accountId)
            ->whereHas('entry', fn ($q) => $q->where('status', 'approved'))
            ->selectRaw('COALESCE(SUM(debit),0) as d, COALESCE(SUM(credit),0) as c')
            ->first();

        return (int) $row->d - (int) $row->c;
    }
}
