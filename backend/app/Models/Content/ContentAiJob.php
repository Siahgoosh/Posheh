<?php

namespace App\Models\Content;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContentAiJob extends Model
{
    public const STATUSES = ['queued', 'running', 'completed', 'failed', 'retrying', 'cancelled'];

    public const TYPES = [
        'research', 'brief', 'outline', 'draft', 'seo_audit', 'fact_check',
        'internal_linking', 'image_suggestion', 'refresh', 'repurpose',
    ];

    protected $table = 'content_ai_jobs';

    protected $fillable = [
        'idempotency_key', 'type', 'blog_post_id', 'created_by', 'status',
        'provider', 'model', 'temperature', 'max_tokens',
        'input_payload', 'output_payload',
        'prompt_tokens', 'completion_tokens', 'total_tokens',
        'estimated_cost_toman', 'actual_cost_toman', 'confidence',
        'duration_ms', 'retry_count', 'next_retry_at', 'error',
        'prompt_version', 'started_at', 'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'input_payload' => 'array',
            'output_payload' => 'array',
            'temperature' => 'float',
            'confidence' => 'float',
            'next_retry_at' => 'datetime',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(\App\Models\BlogPost::class, 'blog_post_id');
    }
}
