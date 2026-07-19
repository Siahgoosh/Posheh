<?php

namespace App\Services\Payment;

use App\Enums\PaymentGateway;
use App\Models\Office;
use App\Models\Payment;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Support\Collection;

class PaymentHistoryService
{
    public function listForOffice(Office $office, int $limit = 50): Collection
    {
        return Payment::query()
            ->where('office_id', $office->id)
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn (Payment $payment) => $this->formatPayment($payment));
    }

    public function walletTransactions(Office $office, int $limit = 50): Collection
    {
        $wallet = $office->wallet;
        if (! $wallet) {
            return collect();
        }

        return $wallet->transactions()
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn (WalletTransaction $tx) => [
                'id' => $tx->id,
                'type' => $tx->type,
                'type_label' => $tx->type === 'credit' ? 'واریز' : 'برداشت',
                'amount' => (int) $tx->amount,
                'balance_after' => (int) $tx->balance_after,
                'description' => $tx->description,
                'created_at' => $tx->created_at?->toIso8601String(),
            ]);
    }

    public function showForOffice(Office $office, int $paymentId, PaymentInvoiceService $invoices): array
    {
        $payment = Payment::where('office_id', $office->id)->findOrFail($paymentId);

        return $this->formatPayment($payment, detailed: true, invoices: $invoices);
    }

    public function assignInvoiceNumber(Payment $payment): Payment
    {
        if ($payment->invoice_number) {
            return $payment;
        }

        $prefix = 'POS-'.now()->format('Ymd');
        $last = Payment::where('invoice_number', 'like', $prefix.'-%')
            ->orderByDesc('id')
            ->value('invoice_number');

        $seq = $last ? ((int) substr($last, -4)) + 1 : 1;
        $payment->update(['invoice_number' => sprintf('%s-%04d', $prefix, $seq)]);

        return $payment->fresh();
    }

    /** @return array<string, mixed> */
    public function formatPayment(Payment $payment, bool $detailed = false, ?PaymentInvoiceService $invoices = null): array
    {
        $purpose = $payment->metadata['purpose'] ?? 'subscription';
        $gateway = $payment->gateway instanceof PaymentGateway ? $payment->gateway : PaymentGateway::from((string) $payment->gateway);

        $data = [
            'id' => $payment->id,
            'invoice_number' => $payment->invoice_number,
            'purpose' => $purpose,
            'purpose_label' => $this->purposeLabel($purpose),
            'status' => $payment->status,
            'status_label' => $this->statusLabel($payment->status),
            'gateway' => $gateway->value,
            'gateway_label' => $gateway->label(),
            'amount' => (int) $payment->amount,
            'original_amount' => (int) ($payment->original_amount ?? $payment->amount),
            'discount_amount' => (int) ($payment->discount_amount ?? 0),
            'currency' => $payment->currency ?? 'IRT',
            'ref_id' => $payment->ref_id,
            'plan_name' => $payment->metadata['plan_name'] ?? null,
            'paid_at' => $payment->paid_at?->toIso8601String(),
            'created_at' => $payment->created_at?->toIso8601String(),
        ];

        if ($detailed && $invoices) {
            $data['invoice'] = $invoices->build($payment);
        }

        return $data;
    }

    private function purposeLabel(string $purpose): string
    {
        return match ($purpose) {
            'subscription' => 'خرید اشتراک',
            'wallet_topup' => 'شارژ کیف پول',
            'manual_credit' => 'شارژ دستی توسط ادمین',
            default => 'پرداخت',
        };
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'paid' => 'پرداخت شده',
            'pending' => 'در انتظار پرداخت',
            'failed' => 'ناموفق',
            'cancelled' => 'لغو شده',
            default => $status,
        };
    }
}
