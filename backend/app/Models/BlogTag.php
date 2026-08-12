<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class BlogTag extends Model
{
    protected $fillable = [
        'slug', 'name', 'description', 'seo_title', 'meta_description',
        'is_indexable', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_indexable' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(BlogPost::class, 'blog_post_tag');
    }
}
