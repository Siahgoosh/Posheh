<?php

namespace App\Models\Content;

use Illuminate\Database\Eloquent\Model;

class ContentAiCostLimit extends Model
{
    protected $table = 'content_ai_cost_limits';

    protected $fillable = [
        'scope', 'limit_toman', 'limit_tokens', 'is_active', 'meta',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'meta' => 'array',
        ];
    }
}
