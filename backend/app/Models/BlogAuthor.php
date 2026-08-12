<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BlogAuthor extends Model
{
    protected $fillable = [
        'slug', 'name', 'bio', 'avatar', 'role', 'social_links',
        'is_indexable', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'social_links' => 'array',
            'is_indexable' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function posts(): HasMany
    {
        return $this->hasMany(BlogPost::class, 'blog_author_id');
    }
}
