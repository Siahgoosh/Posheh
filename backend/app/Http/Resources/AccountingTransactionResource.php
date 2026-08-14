<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Morilog\Jalali\Jalalian;

class AccountingTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $typeLabels = [
            'income' => 'درآمد',
            'expense' => 'هزینه',
            'transfer_in' => 'انتقال ورودی',
            'transfer_out' => 'انتقال خروجی',
            'receipt' => 'دریافت',
            'payment' => 'پرداخت',
        ];

        return [
            'id' => $this->id,
            'transaction_number' => $this->transaction_number,
            'type' => $this->type,
            'type_label' => $typeLabels[$this->type] ?? $this->type,
            'status' => $this->status ?? 'approved',
            'category' => $this->category,
            'payment_method' => $this->payment_method,
            'amount' => (int) $this->amount,
            'title' => $this->title,
            'description' => $this->description,
            'transaction_date' => $this->transaction_date?->format('Y-m-d'),
            'transaction_date_jalali' => $this->transaction_date
                ? Jalalian::fromDateTime($this->transaction_date)->format('Y/m/d')
                : null,
            'reference' => $this->reference,
            'property_id' => $this->property_id,
            'crm_deal_id' => $this->crm_deal_id,
            'commission_id' => $this->commission_id,
            'account' => $this->whenLoaded('account', fn () => $this->account ? [
                'id' => $this->account->id,
                'code' => $this->account->code,
                'name' => $this->account->name,
            ] : null),
            'cash_account' => $this->whenLoaded('cashAccount', fn () => $this->cashAccount ? [
                'id' => $this->cashAccount->id,
                'name' => $this->cashAccount->name,
                'kind' => $this->cashAccount->kind,
            ] : null),
            'created_at_jalali' => $this->created_at
                ? Jalalian::fromDateTime($this->created_at)->format('Y/m/d H:i')
                : null,
        ];
    }
}
