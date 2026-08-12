<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiUsageLog extends Model
{
    protected $fillable = [
        'office_id', 'user_id', 'feature', 'provider', 'model',
        'prompt_tokens', 'completion_tokens', 'total_tokens', 'cost_toman',
        'status', 'meta',
    ];

    protected function casts(): array
    {
        return ['meta' => 'array'];
    }
}
