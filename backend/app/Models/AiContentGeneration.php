<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiContentGeneration extends Model
{
    protected $fillable = [
        'office_id', 'user_id', 'type', 'tone', 'property_id',
        'input', 'output', 'meta', 'provider', 'tokens_used',
    ];

    protected function casts(): array
    {
        return [
            'input' => 'array',
            'meta' => 'array',
        ];
    }

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }
}
