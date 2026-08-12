<?php

namespace App\Services\Accounting;

use App\Models\AccountingAccount;
use App\Models\AccountingAuditLog;
use App\Models\AccountingCashAccount;
use App\Models\AccountingSettlement;
use App\Models\AccountingSettlementItem;
use App\Models\Commission;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SettlementService
{
    public function __construct(
        private readonly AccountingBootstrapService $bootstrap,
        private readonly LedgerService $ledger,
    ) {}

    private function nextNumber(int $officeId): string
    {
        $n = AccountingSettlement::where('office_id', $officeId)->count() + 1;

        return sprintf('ST-%s-%05d', now()->format('Ym'), $n);
    }

    public function settleCommission(User $user, int $commissionId, array $data): AccountingSettlement
    {
        if (! $user->canManageOffice()) {
            throw ValidationException::withMessages(['auth' => ['فقط مدیر می‌تواند تسویه کند.']]);
        }
        $this->bootstrap->ensureForUser($user);
        $officeId = (int) $user->office_id;
        $commission = Commission::where('office_id', $officeId)->findOrFail($commissionId);
        if ($commission->status === 'paid') {
            throw ValidationException::withMessages(['commission' => ['این کمیسیون قبلاً تسویه شده است.']]);
        }

        $consultantShare = (int) ($commission->consultant_share_amount ?: $commission->commission_amount);
        $amount = (int) ($data['amount'] ?? $consultantShare);
        if ($amount < 1 || $amount > $consultantShare) {
            throw ValidationException::withMessages(['amount' => ['مبلغ تسویه نامعتبر است.']]);
        }

        $cash = null;
        if (! empty($data['cash_account_id'])) {
            $cash = AccountingCashAccount::where('office_id', $officeId)->findOrFail($data['cash_account_id']);
        } else {
            $cash = AccountingCashAccount::where('office_id', $officeId)->where('is_active', true)->orderBy('id')->first();
        }
        if (! $cash?->ledger_account_id) {
            throw ValidationException::withMessages(['cash_account_id' => ['حساب نقد/بانک مقصد لازم است.']]);
        }

        $payable = AccountingAccount::where('office_id', $officeId)->where('code', '2102')->first();
        $payoutExpense = AccountingAccount::where('office_id', $officeId)->where('code', '5201')->first();
        if (! $payable || ! $payoutExpense) {
            throw ValidationException::withMessages(['account' => ['حساب بدهی مشاور آماده نیست.']]);
        }

        return DB::transaction(function () use ($user, $officeId, $commission, $amount, $cash, $payable, $payoutExpense, $data) {
            // Ensure liability was recognized: Debit expense / Credit consultant payable (if not already)
            if (! $commission->office_share_amount && ! $commission->consultant_share_amount) {
                $total = (int) $commission->commission_amount;
                $consultantShare = $total; // default: full amount is consultant share when settling payout
                $officeShare = 0;
                // Prefer split: if settings imply rates were on deal value already stored as commission_amount = consultant share historically
                $commission->update([
                    'consultant_share_amount' => $consultantShare,
                    'office_share_amount' => $officeShare,
                ]);
            }

            $entry = $this->ledger->post(
                $user,
                'settlement',
                $commission->id,
                'تسویه کمیسیون مشاور #'.$commission->id,
                [
                    ['account_id' => $payable->id, 'debit' => $amount, 'credit' => 0, 'party_type' => User::class, 'party_id' => $commission->user_id],
                    ['account_id' => $cash->ledger_account_id, 'debit' => 0, 'credit' => $amount],
                ],
                $data['settlement_date'] ?? now()->toDateString(),
            );

            $settlement = AccountingSettlement::create([
                'office_id' => $officeId,
                'settlement_number' => $this->nextNumber($officeId),
                'kind' => 'consultant',
                'amount' => $amount,
                'settlement_date' => $data['settlement_date'] ?? now()->toDateString(),
                'payment_method' => $data['payment_method'] ?? ($cash->kind === 'bank' ? 'bank' : 'cash'),
                'cash_account_id' => $cash->id,
                'consultant_id' => $commission->user_id,
                'party_type' => User::class,
                'party_id' => $commission->user_id,
                'crm_deal_id' => $commission->crm_deal_id,
                'journal_entry_id' => $entry->id,
                'reference' => $data['reference'] ?? null,
                'description' => $data['description'] ?? 'تسویه کمیسیون',
                'status' => 'approved',
                'created_by' => $user->id,
            ]);

            AccountingSettlementItem::create([
                'office_id' => $officeId,
                'settlement_id' => $settlement->id,
                'commission_id' => $commission->id,
                'amount' => $amount,
                'label' => 'سهم مشاور',
            ]);

            $commission->update([
                'status' => 'paid',
                'paid_at' => now(),
                'accounting_settlement_id' => $settlement->id,
            ]);

            AccountingAuditLog::create([
                'office_id' => $officeId,
                'user_id' => $user->id,
                'action' => 'settlement.commission',
                'auditable_type' => AccountingSettlement::class,
                'auditable_id' => $settlement->id,
                'after' => ['commission_id' => $commission->id, 'amount' => $amount],
                'ip' => request()?->ip(),
            ]);

            return $settlement->load(['items', 'consultant']);
        });
    }

    public function recognizeCommission(User $user, Commission $commission): void
    {
        $this->bootstrap->ensureForUser($user);
        $officeId = (int) $commission->office_id;
        $total = (int) $commission->commission_amount;
        if ($total < 1) {
            return;
        }

        $consultantShare = (int) ($commission->consultant_share_amount ?: $total);
        $officeShare = (int) ($commission->office_share_amount ?? max(0, $total - $consultantShare));
        if (! $commission->consultant_share_amount) {
            // Default split 60% office / 40% consultant when not set
            $officeShare = (int) round($total * 0.6);
            $consultantShare = $total - $officeShare;
            $commission->update([
                'office_share_amount' => $officeShare,
                'consultant_share_amount' => $consultantShare,
            ]);
        }

        $income = AccountingAccount::where('office_id', $officeId)->where('code', '4101')->first();
        $receivable = AccountingAccount::where('office_id', $officeId)->where('code', '1201')->first();
        $payable = AccountingAccount::where('office_id', $officeId)->where('code', '2102')->first();
        if (! $income || ! $receivable || ! $payable) {
            return;
        }

        // Debit receivable (total), Credit income (office share) + Credit consultant payable (consultant share)
        $lines = [
            ['account_id' => $receivable->id, 'debit' => $total, 'credit' => 0, 'memo' => 'کمیسیون معامله'],
        ];
        if ($officeShare > 0) {
            $lines[] = ['account_id' => $income->id, 'debit' => 0, 'credit' => $officeShare, 'memo' => 'سهم دفتر'];
        }
        if ($consultantShare > 0) {
            $lines[] = [
                'account_id' => $payable->id,
                'debit' => 0,
                'credit' => $consultantShare,
                'memo' => 'سهم مشاور',
                'party_type' => User::class,
                'party_id' => $commission->user_id,
            ];
        }
        // If only consultant share equals total and office 0, income still needs balancing — credit income for office 0 case:
        if ($officeShare === 0 && $consultantShare === $total) {
            // receivable = consultant payable only — OK balanced
        }

        $this->ledger->post($user, 'commission', $commission->id, 'شناسایی کمیسیون #'.$commission->id, $lines);
    }

    public function list(User $user)
    {
        $this->bootstrap->ensureForUser($user);

        return AccountingSettlement::with(['consultant', 'items.commission'])
            ->where('office_id', $user->office_id)
            ->orderByDesc('settlement_date')
            ->paginate(30);
    }
}
