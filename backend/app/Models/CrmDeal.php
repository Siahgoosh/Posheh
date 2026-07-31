<?php

namespace App\Models;

use App\Traits\BelongsToOffice;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmDeal extends Model
{
    use BelongsToOffice;

    protected $fillable = [
        'office_id', 'assigned_to', 'property_id', 'title',
        'contact_name', 'contact_mobile', 'stage', 'value', 'offer_amount', 'notes',
        'expected_close_at', 'lead_score', 'priority', 'source', 'follow_up_at',
    ];

    protected function casts(): array
    {
        return [
            'expected_close_at' => 'datetime',
            'follow_up_at' => 'datetime',
        ];
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function activities(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(CrmActivity::class)->orderByDesc('created_at');
    }
}
