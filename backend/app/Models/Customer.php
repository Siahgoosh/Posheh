<?php

namespace App\Models;

use App\Enums\PropertyType;
use App\Traits\BelongsToOffice;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use BelongsToOffice;

    protected $appends = ['need_label'];

    protected $fillable = [
        'office_id',
        'created_by',
        'assigned_to',
        'name',
        'mobile',
        'national_id',
        'priority',
        'budget_min',
        'budget_max',
        'preferred_type',
        'preferred_city',
        'preferred_district',
        'min_area',
        'max_area',
        'min_rooms',
        'notes',
    ];

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

    public function getNeedLabelAttribute(): ?string
    {
        if (! $this->preferred_type) {
            return null;
        }

        try {
            return PropertyType::from($this->preferred_type)->label();
        } catch (\ValueError) {
            return $this->preferred_type;
        }
    }
}
