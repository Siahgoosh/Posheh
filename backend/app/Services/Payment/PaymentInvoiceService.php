<?php

namespace App\Services\Payment;

use App\Enums\PaymentGateway;
use App\Models\Office;
use App\Models\Payment;
use App\Services\Settings\SystemSettingsService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class PaymentInvoiceService
{
    public function __construct(
        private readonly SystemSettingsService $settings,
    ) {}

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
    public function build(Payment $payment): array
    {
        $payment = $this->assignInvoiceNumber($payment);
        $office = $payment->office ?? Office::find($payment->office_id);
        $gateway = $payment->gateway instanceof PaymentGateway ? $payment->gateway : PaymentGateway::tryFrom((string) $payment->gateway);
        $purpose = ($payment->metadata ?? [])['purpose'] ?? 'subscription';
        $vatPercent = (int) ($this->settings->get('accounting_default_vat_percent', '0') ?: 0);
        $amount = (int) $payment->amount;
        $vatAmount = $vatPercent > 0 ? (int) round($amount * $vatPercent / 100) : 0;

        return [
            'invoice_number' => $payment->invoice_number,
            'invoice_type' => $payment->status === 'paid' ? 'receipt' : 'proforma',
            'invoice_type_label' => $payment->status === 'paid' ? 'رسید پرداخت' : 'پیش‌فاکتور',
            'status' => $payment->status,
            'status_label' => $payment->status === 'paid' ? 'پرداخت شده' : 'در انتظار پرداخت',
            'issued_at' => ($payment->paid_at ?? $payment->created_at)?->toIso8601String(),
            'seller' => [
                'name' => $this->settings->get('app_public_name', 'پوشه'),
                'support_phone' => $this->settings->get('support_phone', ''),
                'support_email' => $this->settings->get('support_email', 'Info@posheapp.ir'),
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
            'gateway_label' => $gateway?->label() ?? (string) $payment->gateway,
            'ref_id' => $payment->ref_id,
            'currency' => 'تومان',
        ];
    }

    public function pdfPath(Payment $payment): string
    {
        $invoice = $this->build($payment);
        $html = view('invoices.payment', ['invoice' => $invoice])->render();
        $pdf = Pdf::loadHTML($html)->setPaper('a4');
        $path = 'invoices/'.($payment->invoice_number ?? 'payment-'.$payment->id).'.pdf';
        $path = preg_replace('/[^a-zA-Z0-9._\-\/]/', '-', $path) ?: 'invoices/payment-'.$payment->id.'.pdf';

        Storage::disk('public')->makeDirectory('invoices');
        Storage::disk('public')->put($path, $pdf->output());

        return $path;
    }

    public function downloadResponse(Payment $payment): \Symfony\Component\HttpFoundation\Response
    {
        $payment = $this->assignInvoiceNumber($payment);
        $invoice = $this->build($payment);
        $html = view('invoices.payment', ['invoice' => $invoice])->render();
        $filename = ($payment->invoice_number ?? 'invoice-'.$payment->id).'.pdf';

        return Pdf::loadHTML($html)
            ->setPaper('a4')
            ->download($filename);
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
