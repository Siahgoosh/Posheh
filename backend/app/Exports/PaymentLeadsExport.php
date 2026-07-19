<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class PaymentLeadsExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(private readonly Collection $rows) {}

    public function collection(): Collection
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return [
            'شناسه پرداخت',
            'نام',
            'موبایل',
            'دفتر',
            'پلن',
            'مبلغ (تومان)',
            'وضعیت',
            'درگاه',
            'کد تخفیف',
            'تاریخ',
        ];
    }

    public function map($payment): array
    {
        return [
            $payment->id,
            $payment->user?->name ?? ($payment->metadata['user_name'] ?? '—'),
            $payment->user_phone ?? $payment->user?->mobile ?? '—',
            $payment->office?->name ?? '—',
            $payment->metadata['plan_name'] ?? '—',
            $payment->amount,
            $payment->status,
            $payment->gateway?->value ?? $payment->gateway,
            $payment->metadata['discount_code'] ?? '—',
            $payment->created_at?->format('Y-m-d H:i'),
        ];
    }
}
