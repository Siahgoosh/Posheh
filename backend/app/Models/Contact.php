<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contact extends Model
{
    protected $fillable = [
        'office_id', 'created_by', 'assigned_to', 'name', 'mobile', 'email',
        'type', 'status', 'source', 'tags', 'notes', 'last_contact_at',
        'budget_min', 'budget_max', 'preferred_areas', 'property_interest',
        'rooms_min', 'area_min',
    ];

    protected function casts(): array
    {
        return [
            'tags' => 'array',
            'preferred_areas' => 'array',
            'last_contact_at' => 'datetime',
        ];
    }

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function deals(): HasMany
    {
        return $this->hasMany(Deal::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(ContactActivity::class);
    }
}
