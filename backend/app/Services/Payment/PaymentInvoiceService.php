<?php

namespace App\Services\Payment;

use App\Enums\PaymentGateway;
use App\Models\Office;
use App\Models\Payment;
use App\Services\Settings\SystemSettingsService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Morilog\Jalali\Jalalian;
use Symfony\Component\HttpFoundation\Response;

class PaymentInvoiceService
{
    public function __construct(
        private readonly SystemSettingsService $settings,
        private readonly InvoicePdfRenderer $pdfRenderer,
    ) {}

    public function assignInvoiceNumber(Payment $payment): Payment
    {
        if (! empty($payment->invoice_number)) {
            return $payment;
        }

        $number = $this->generateInvoiceNumber($payment);

        if (Schema::hasColumn('payments', 'invoice_number')) {
            try {
                $payment->update(['invoice_number' => $number]);

                return $payment->fresh();
            } catch (\Throwable $e) {
                Log::warning('Could not save invoice_number', [
                    'payment_id' => $payment->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $payment->setAttribute('invoice_number', $number);

        return $payment;
    }

    /** @return array<string, mixed> */
    public function build(Payment $payment): array
    {
        $payment = $this->assignInvoiceNumber($payment);
        $office = $payment->office ?? Office::find($payment->office_id);
        $gateway = $this->resolveGateway($payment);
        $purpose = ($payment->metadata ?? [])['purpose'] ?? 'subscription';
        $vatPercent = (int) ($this->settings->get('accounting_default_vat_percent', '0') ?: 0);
        $amount = (int) $payment->amount;
        $vatAmount = $vatPercent > 0 ? (int) round($amount * $vatPercent / 100) : 0;

        $issuedAt = $payment->paid_at ?? $payment->created_at;

        return [
            'invoice_number' => $payment->invoice_number ?? $this->generateInvoiceNumber($payment),
            'invoice_type' => $payment->status === 'paid' ? 'receipt' : 'proforma',
            'invoice_type_label' => $payment->status === 'paid' ? 'رسید پرداخت' : 'پیش‌فاکتور',
            'status' => $payment->status,
            'status_label' => $payment->status === 'paid' ? 'پرداخت شده' : 'در انتظار پرداخت',
            'issued_at' => $issuedAt?->toIso8601String(),
            'issued_at_jalali' => $issuedAt ? Jalalian::fromDateTime($issuedAt)->format('Y/m/d — H:i') : Jalalian::now()->format('Y/m/d — H:i'),
            'seller' => [
                'name' => (string) $this->settings->get('app_public_name', 'پوشه'),
                'support_phone' => (string) $this->settings->get('support_phone', ''),
                'support_email' => (string) $this->settings->get('support_email', 'Info@posheapp.ir'),
            ],
            'buyer' => [
                'office_name' => $office?->name,
                'user_name' => ($payment->metadata ?? [])['user_name'] ?? null,
                'user_phone' => $payment->user_phone,
            ],
            'items' => [[
                'title' => $this->itemTitle($payment, $purpose),
                'quantity' => 1,
                'unit_price' => (int) ($payment->original_amount ?? $amount),
                'discount' => (int) ($payment->discount_amount ?? 0),
                'total' => $amount,
            ]],
            'subtotal' => (int) ($payment->original_amount ?? $amount),
            'discount' => (int) ($payment->discount_amount ?? 0),
            'vat_percent' => $vatPercent,
            'vat_amount' => $vatAmount,
            'total' => $amount + $vatAmount,
            'gateway_label' => $gateway?->label() ?? (string) $payment->getRawOriginal('gateway'),
            'ref_id' => $payment->ref_id,
            'currency' => 'تومان',
        ];
    }

    public function printResponse(Payment $payment): Response
    {
        $invoice = $this->build($payment);

        return response()->view('invoices.payment-invoice', [
            'invoice' => $invoice,
            'forPdf' => false,
        ]);
    }

    public function pdfResponse(Payment $payment): Response
    {
        $payment = $this->assignInvoiceNumber($payment);
        $invoice = $this->build($payment);
        $html = view('invoices.payment-invoice', [
            'invoice' => $invoice,
            'forPdf' => true,
        ])->render();

        $filename = ($payment->invoice_number ?? 'invoice-'.$payment->id).'.pdf';

        try {
            return $this->pdfRenderer->render($html, $filename);
        } catch (\Throwable $e) {
            Log::error('mPDF invoice failed, falling back to HTML', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);

            return $this->printResponse($payment);
        }
    }

    public function downloadResponse(Payment $payment): Response
    {
        return $this->pdfResponse($payment);
    }

    private function generateInvoiceNumber(Payment $payment): string
    {
        return sprintf('POS-%s-%04d', now()->format('Ymd'), $payment->id);
    }

    private function resolveGateway(Payment $payment): ?PaymentGateway
    {
        $raw = $payment->getRawOriginal('gateway');

        if ($raw instanceof PaymentGateway) {
            return $raw;
        }

        return PaymentGateway::tryFrom((string) $raw);
    }

    private function itemTitle(Payment $payment, string $purpose): string
    {
        return match ($purpose) {
            'wallet_topup' => 'شارژ کیف پول پوشه',
            'manual_credit' => ($payment->metadata ?? [])['description'] ?? 'شارژ دستی کیف پول',
            default => 'اشتراک '.(($payment->metadata ?? [])['plan_name'] ?? 'پوشه'),
        };
    }
}
