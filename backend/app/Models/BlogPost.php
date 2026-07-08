<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class BlogPost extends Model
{
    protected $fillable = [
        'slug',
        'title',
        'excerpt',
        'content',
        'cover_image',
        'meta_title',
        'meta_description',
        'keywords',
        'author_name',
        'reading_time',
        'views',
        'is_published',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public static function makeSlug(string $title): string
    {
        $slug = Str::slug($title, '-', 'fa');

        if ($slug === '') {
            $slug = 'post-'.Str::random(8);
        }

        return $slug;
    }
}
