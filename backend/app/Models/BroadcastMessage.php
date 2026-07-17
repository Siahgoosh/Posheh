<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BroadcastMessage extends Model
{
    protected $fillable = [
        'created_by',
        'title',
        'body',
        'link_url',
        'image_url',
        'action_label',
        'priority',
        'target_platforms',
        'target_roles',
        'style',
        'is_active',
        'starts_at',
        'ends_at',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'target_platforms' => 'array',
            'target_roles' => 'array',
            'style' => 'array',
            'is_active' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'sent_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reads(): HasMany
    {
        return $this->hasMany(BroadcastMessageRead::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            });
    }
}
