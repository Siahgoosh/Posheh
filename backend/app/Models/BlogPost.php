<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class BlogPost extends Model
{
    public const REVIEW_DRAFT = 'draft';

    public const REVIEW_SEO = 'seo_review';

    public const REVIEW_CONTENT = 'content_review';

    public const REVIEW_APPROVED = 'approved';

    public const REVIEW_PUBLISHED = 'published';

    public const REVIEW_REJECTED = 'rejected';

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
        'canonical_url',
        'robots_directive',
        'keywords',
        'focus_keyword',
        'secondary_keywords',
        'search_intent',
        'business_intent',
        'faq',
        'related_slugs',
        'cta_text',
        'cta_url',
        'author_name',
        'reading_time',
        'views',
        'is_published',
        'review_status',
        'rebuild_locked',
        'quality_scores',
        'content_brief',
        'image_prompt',
        'published_at',
        'scheduled_at',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'rebuild_locked' => 'boolean',
            'published_at' => 'datetime',
            'scheduled_at' => 'datetime',
            'faq' => 'array',
            'related_slugs' => 'array',
            'secondary_keywords' => 'array',
            'quality_scores' => 'array',
            'content_brief' => 'array',
        ];
    }

    public function versions(): HasMany
    {
        return $this->hasMany(BlogPostVersion::class);
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
