<?php

namespace App\Models\Content;

use Illuminate\Database\Eloquent\Model;

class ContentAiTaskConfig extends Model
{
    protected $table = 'content_ai_task_configs';

    protected $fillable = [
        'task_key', 'label', 'provider', 'model', 'temperature', 'max_tokens',
        'system_prompt', 'timeout_sec', 'retry_max', 'cost_limit_per_job', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'temperature' => 'float',
            'is_active' => 'boolean',
        ];
    }
}
