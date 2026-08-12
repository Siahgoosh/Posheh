<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class BlogPost extends Model
{
    public const REVIEW_DRAFT = 'draft';

    public const REVIEW_IN_REVIEW = 'in_review';

    public const REVIEW_SEO = 'seo_review';

    public const REVIEW_CONTENT = 'content_review';

    public const REVIEW_APPROVED = 'approved';

    public const REVIEW_SCHEDULED = 'scheduled';

    public const REVIEW_PUBLISHED = 'published';

    public const REVIEW_UNPUBLISHED = 'unpublished';

    public const REVIEW_ARCHIVED = 'archived';

    public const REVIEW_REJECTED = 'rejected';

    protected $fillable = [
        'blog_category_id',
        'blog_author_id',
        'slug',
        'preview_token',
        'category_slug',
        'category_label',
        'pillar_slug',
        'title',
        'excerpt',
        'content',
        'cover_image',
        'meta_title',
        'meta_description',
        'og_title',
        'og_description',
        'og_image',
        'canonical_url',
        'robots_directive',
        'keywords',
        'focus_keyword',
        'secondary_keywords',
        'search_intent',
        'business_intent',
        'funnel_stage',
        'faq',
        'related_slugs',
        'cta_text',
        'cta_url',
        'cro_cta_key',
        'author_name',
        'reading_time',
        'views',
        'view_score',
        'is_published',
        'is_featured',
        'is_editors_pick',
        'review_status',
        'rebuild_locked',
        'quality_scores',
        'content_brief',
        'image_prompt',
        'published_at',
        'scheduled_at',
        'content_updated_at',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'is_featured' => 'boolean',
            'is_editors_pick' => 'boolean',
            'rebuild_locked' => 'boolean',
            'published_at' => 'datetime',
            'scheduled_at' => 'datetime',
            'content_updated_at' => 'datetime',
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

    public function category(): BelongsTo
    {
        return $this->belongsTo(BlogCategory::class, 'blog_category_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(BlogAuthor::class, 'blog_author_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(BlogTag::class, 'blog_post_tag');
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
