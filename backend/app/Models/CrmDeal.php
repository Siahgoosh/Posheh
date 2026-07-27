<?php

namespace App\Models;

use App\Traits\BelongsToOffice;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmDeal extends Model
{
    use BelongsToOffice;

    protected $fillable = [
        'office_id',
        'stage_id',
        'property_id',
        'assigned_to',
        'created_by',
        'title',
        'customer_name',
        'customer_mobile',
        'value',
        'lead_score',
        'notes',
        'next_follow_up_at',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'next_follow_up_at' => 'datetime',
            'lead_score' => 'integer',
            'value' => 'integer',
        ];
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(CrmStage::class, 'stage_id');
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
