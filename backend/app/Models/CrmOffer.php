<?php

namespace App\Models;

use App\Traits\BelongsToOffice;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CrmOffer extends Model
{
    use BelongsToOffice, SoftDeletes;

    protected $fillable = [
        'office_id', 'negotiation_id', 'crm_deal_id', 'property_id', 'customer_id',
        'created_by', 'side', 'amount', 'payment_terms', 'deposit', 'installments',
        'deadline_at', 'status', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'deposit' => 'integer',
            'deadline_at' => 'datetime',
        ];
    }

    public function negotiation(): BelongsTo
    {
        return $this->belongsTo(CrmNegotiation::class, 'negotiation_id');
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
