<?php

namespace App\Models;

use App\Traits\BelongsToOffice;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Customer extends Model
{
    use BelongsToOffice;

    protected $fillable = [
        'office_id',
        'created_by',
        'assigned_to',
        'name',
        'first_name',
        'last_name',
        'mobile',
        'mobile_secondary',
        'phone',
        'national_id',
        'priority',
        'roles',
        'source',
        'source_id',
        'status',
        'lead_score',
        'budget_min',
        'budget_max',
        'preferred_type',
        'preferred_city',
        'preferred_district',
        'city',
        'min_area',
        'max_area',
        'min_rooms',
        'notes',
        'last_contacted_at',
        'next_follow_up_at',
    ];

    protected function casts(): array
    {
        return [
            'roles' => 'array',
            'budget_min' => 'integer',
            'budget_max' => 'integer',
            'lead_score' => 'integer',
            'last_contacted_at' => 'datetime',
            'next_follow_up_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function visits(): HasMany
    {
        return $this->hasMany(PropertyVisit::class);
    }

    public function deals(): HasMany
    {
        return $this->hasMany(CrmDeal::class);
    }

    public function tags(): MorphToMany
    {
        return $this->morphToMany(CrmTag::class, 'taggable', 'crm_taggables');
    }
}
