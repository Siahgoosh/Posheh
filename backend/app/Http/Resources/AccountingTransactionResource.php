<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Morilog\Jalali\Jalalian;

class AccountingTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'type_label' => $this->type === 'income' ? 'درآمد' : 'هزینه',
            'category' => $this->category,
            'amount' => (int) $this->amount,
            'title' => $this->title,
            'description' => $this->description,
            'transaction_date' => $this->transaction_date?->format('Y-m-d'),
            'transaction_date_jalali' => $this->transaction_date
                ? Jalalian::fromDateTime($this->transaction_date)->format('Y/m/d')
                : null,
            'reference' => $this->reference,
            'created_at_jalali' => $this->created_at
                ? Jalalian::fromDateTime($this->created_at)->format('Y/m/d H:i')
                : null,
        ];
    }
}
