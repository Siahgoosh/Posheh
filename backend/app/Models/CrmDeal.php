<?php

namespace App\Models;

use App\Traits\BelongsToOffice;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class CrmDeal extends Model
{
    use BelongsToOffice;

    protected $fillable = [
        'office_id', 'assigned_to', 'created_by', 'property_id', 'customer_id',
        'title', 'contact_name', 'contact_mobile', 'stage', 'value', 'offer_amount',
        'notes', 'lost_reason', 'lost_reason_note', 'expected_close_at',
        'lead_score', 'probability', 'priority', 'source', 'source_id',
        'follow_up_at', 'last_contacted_at', 'first_contacted_at', 'next_action',
        'campaign_id', 'deal_status',
    ];

    protected function casts(): array
    {
        return [
            'expected_close_at' => 'datetime',
            'follow_up_at' => 'datetime',
            'last_contacted_at' => 'datetime',
            'first_contacted_at' => 'datetime',
            'value' => 'integer',
            'offer_amount' => 'integer',
            'lead_score' => 'integer',
            'probability' => 'integer',
        ];
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function crmSource(): BelongsTo
    {
        return $this->belongsTo(CrmSource::class, 'source_id');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(CrmActivity::class)->orderByDesc('created_at');
    }

    public function followUps(): HasMany
    {
        return $this->hasMany(CrmFollowUp::class, 'crm_deal_id')->orderBy('due_at');
    }

    public function tags(): MorphToMany
    {
        return $this->morphToMany(CrmTag::class, 'taggable', 'crm_taggables');
    }

    public function scoreBand(): string
    {
        $s = (int) $this->lead_score;
        if ($s <= 20) {
            return 'cold';
        }
        if ($s <= 40) {
            return 'low';
        }
        if ($s <= 60) {
            return 'warm';
        }
        if ($s <= 80) {
            return 'hot';
        }

        return 'very_hot';
    }
}
