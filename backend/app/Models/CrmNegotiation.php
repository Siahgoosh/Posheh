<?php

namespace App\Models;

use App\Traits\BelongsToOffice;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CrmNegotiation extends Model
{
    use BelongsToOffice, SoftDeletes;

    protected $fillable = [
        'office_id', 'crm_deal_id', 'property_id', 'customer_id', 'agent_id',
        'buyer_name', 'seller_name', 'initial_price', 'current_price',
        'target_price', 'minimum_acceptable', 'status', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'initial_price' => 'integer',
            'current_price' => 'integer',
            'target_price' => 'integer',
            'minimum_acceptable' => 'integer',
        ];
    }

    public function offers(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(CrmOffer::class, 'negotiation_id')->orderBy('created_at');
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function deal(): BelongsTo
    {
        return $this->belongsTo(CrmDeal::class, 'crm_deal_id');
    }
}
