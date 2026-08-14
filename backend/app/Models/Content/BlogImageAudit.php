<?php

namespace App\Models\Content;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlogImageAudit extends Model
{
    protected $table = 'blog_image_audits';

    protected $fillable = [
        'blog_post_id', 'image_status', 'priority', 'opportunity_score',
        'should_generate', 'skip_reason', 'recommended_type',
        'existing_image_count', 'has_hero', 'hero_url', 'findings', 'audited_at',
    ];

    protected function casts(): array
    {
        return [
            'should_generate' => 'boolean',
            'has_hero' => 'boolean',
            'findings' => 'array',
            'audited_at' => 'datetime',
        ];
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(\App\Models\BlogPost::class, 'blog_post_id');
    }
}
