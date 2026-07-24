<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    protected $fillable = [
        'code',
        'type',
        'value',
        'max_uses',
        'used_count',
        'plan_slugs',
        'starts_at',
        'ends_at',
        'is_active',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'plan_slugs' => 'array',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }
}
