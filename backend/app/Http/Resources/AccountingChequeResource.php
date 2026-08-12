<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Morilog\Jalali\Jalalian;

class AccountingChequeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $statusLabels = [
            'received' => 'دریافت‌شده',
            'pending' => 'در انتظار وصول',
            'cleared' => 'وصول‌شده',
            'bounced' => 'برگشتی',
            'returned' => 'مستردشده',
            'spent' => 'خرج‌شده',
            'cancelled' => 'باطل‌شده',
            'issued' => 'صادرشده',
        ];

        return [
            'id' => $this->id,
            'direction' => $this->direction,
            'direction_label' => $this->direction === 'in' ? 'دریافتی' : 'پرداختی',
            'cheque_number' => $this->cheque_number,
            'sayad_id' => $this->sayad_id,
            'amount' => (int) $this->amount,
            'bank_name' => $this->bank_name,
            'branch_name' => $this->branch_name,
            'issuer_name' => $this->issuer_name,
            'issue_date' => $this->issue_date?->format('Y-m-d'),
            'issue_date_jalali' => $this->issue_date
                ? Jalalian::fromDateTime($this->issue_date)->format('Y/m/d')
                : null,
            'due_date' => $this->due_date?->format('Y-m-d'),
            'due_date_jalali' => $this->due_date
                ? Jalalian::fromDateTime($this->due_date)->format('Y/m/d')
                : null,
            'status' => $this->status,
            'status_label' => $statusLabels[$this->status] ?? $this->status,
            'description' => $this->description,
            'property_id' => $this->property_id,
            'crm_deal_id' => $this->crm_deal_id,
        ];
    }
}
