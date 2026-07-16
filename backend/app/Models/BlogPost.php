<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class BlogPost extends Model
{
    protected $fillable = [
        'slug',
        'category_slug',
        'category_label',
        'pillar_slug',
        'title',
        'excerpt',
        'content',
        'cover_image',
        'meta_title',
        'meta_description',
        'keywords',
        'faq',
        'related_slugs',
        'cta_text',
        'cta_url',
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
            'faq' => 'array',
            'related_slugs' => 'array',
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
