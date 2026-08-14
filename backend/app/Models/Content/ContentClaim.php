<?php

namespace App\Models\Content;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContentClaim extends Model
{
    protected $table = 'content_claims';

    protected $fillable = [
        'blog_post_id', 'job_id', 'claim', 'source', 'source_type', 'source_date',
        'confidence', 'risk_level', 'status', 'requires_human',
        'reviewed_by', 'reviewed_at', 'review_note',
    ];

    protected function casts(): array
    {
        return [
            'source_date' => 'date',
            'requires_human' => 'boolean',
            'reviewed_at' => 'datetime',
        ];
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(\App\Models\BlogPost::class, 'blog_post_id');
    }
}
