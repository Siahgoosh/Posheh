<?php

namespace App\Models;

use App\Traits\BelongsToOffice;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmPropertyPresentation extends Model
{
    use BelongsToOffice;

    protected $fillable = [
        'office_id', 'customer_id', 'property_id', 'crm_deal_id', 'agent_id',
        'channel', 'match_score', 'sent_at', 'notes',
    ];

    protected function casts(): array
    {
        return ['sent_at' => 'datetime', 'match_score' => 'integer'];
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
