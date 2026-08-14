<?php

namespace App\Models\Content;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContentImageBrief extends Model
{
    protected $table = 'content_image_briefs';

    protected $fillable = [
        'blog_post_id', 'purpose', 'placement', 'subject', 'aspect_ratio',
        'alt_text', 'caption', 'prompt', 'status', 'auto_publish_image',
    ];

    protected function casts(): array
    {
        return ['auto_publish_image' => 'boolean'];
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(\App\Models\BlogPost::class, 'blog_post_id');
    }
}
