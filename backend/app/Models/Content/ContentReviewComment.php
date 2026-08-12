<?php

namespace App\Models\Content;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContentReviewComment extends Model
{
    protected $table = 'content_review_comments';

    protected $fillable = [
        'blog_post_id', 'user_id', 'section', 'block_ref', 'body', 'status',
    ];

    public function post(): BelongsTo
    {
        return $this->belongsTo(\App\Models\BlogPost::class, 'blog_post_id');
    }
}
