<?php

namespace App\Models\Content;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlogImageJob extends Model
{
    public const STATUSES = [
        'queued', 'processing', 'generated', 'failed', 'retrying',
        'approved', 'rejected', 'published', 'cancelled',
    ];

    protected $table = 'blog_image_jobs';

    protected $fillable = [
        'idempotency_key', 'blog_post_id', 'batch_id', 'created_by', 'status', 'priority',
        'opportunity_score', 'image_status', 'image_type', 'provider', 'model',
        'brief', 'prompt', 'negative_prompt', 'prompt_version', 'variant',
        'storage_path', 'public_url', 'seo_filename', 'alt_text', 'caption',
        'width', 'height', 'bytes', 'mime', 'format',
        'is_ai_generated', 'is_illustrative', 'auto_approve',
        'estimated_cost_toman', 'actual_cost_toman', 'retry_count', 'next_retry_at',
        'error', 'reject_reason', 'reject_note', 'reviewed_by', 'reviewed_at',
        'previous_media_id', 'quality', 'started_at', 'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'brief' => 'array',
            'quality' => 'array',
            'is_ai_generated' => 'boolean',
            'is_illustrative' => 'boolean',
            'auto_approve' => 'boolean',
            'next_retry_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(\App\Models\BlogPost::class, 'blog_post_id');
    }
}
