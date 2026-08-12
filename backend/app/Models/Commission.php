<?php

namespace App\Models;

use App\Traits\BelongsToOffice;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Commission extends Model
{
    use BelongsToOffice;

    protected $fillable = [
        'office_id',
        'user_id',
        'crm_deal_id',
        'property_id',
        'title',
        'base_amount',
        'rate_percent',
        'commission_amount',
        'office_share_amount',
        'consultant_share_amount',
        'status',
        'notes',
        'paid_at',
        'accounting_settlement_id',
    ];

    protected function casts(): array
    {
        return [
            'paid_at' => 'datetime',
            'base_amount' => 'integer',
            'commission_amount' => 'integer',
            'office_share_amount' => 'integer',
            'consultant_share_amount' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function deal(): BelongsTo
    {
        return $this->belongsTo(CrmDeal::class, 'crm_deal_id');
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function settlement(): BelongsTo
    {
        return $this->belongsTo(AccountingSettlement::class, 'accounting_settlement_id');
    }
}
