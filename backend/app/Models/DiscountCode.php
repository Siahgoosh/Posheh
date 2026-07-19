<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DiscountCode extends Model
{
    protected $fillable = [
        'code',
        'type',
        'value',
        'max_uses',
        'used_count',
        'subscription_plan_id',
        'valid_from',
        'valid_until',
        'is_active',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'valid_from' => 'datetime',
            'valid_until' => 'datetime',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function calculateDiscount(int $amountToman): int
    {
        if ($this->type === 'fixed') {
            return min($amountToman, max(0, $this->value));
        }

        return (int) round($amountToman * min(100, $this->value) / 100);
    }

    public function applyTo(int $amountToman): int
    {
        return max(0, $amountToman - $this->calculateDiscount($amountToman));
    }
}
