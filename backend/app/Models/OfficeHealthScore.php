<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfficeHealthScore extends Model
{
    protected $fillable = [
        'office_id',
        'score',
        'factors',
        'calculated_at',
    ];

    protected function casts(): array
    {
        return [
            'factors' => 'array',
            'calculated_at' => 'datetime',
        ];
    }

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }
}
