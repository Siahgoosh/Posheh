<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlogPostVersion extends Model
{
    protected $fillable = [
        'blog_post_id',
        'version',
        'title',
        'content',
        'excerpt',
        'meta_title',
        'meta_description',
        'snapshot',
        'created_by',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'snapshot' => 'array',
        ];
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(BlogPost::class, 'blog_post_id');
    }
}
