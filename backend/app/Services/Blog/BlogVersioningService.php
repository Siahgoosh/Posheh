<?php

namespace App\Services\Blog;

use App\Models\BlogPost;
use App\Models\BlogPostVersion;

class BlogVersioningService
{
    public function snapshot(BlogPost $post, ?string $note = null, ?string $createdBy = null): BlogPostVersion
    {
        $next = (int) $post->versions()->max('version') + 1;

        return $post->versions()->create([
            'version' => max(1, $next),
            'title' => $post->title,
            'content' => $post->content,
            'excerpt' => $post->excerpt,
            'meta_title' => $post->meta_title,
            'meta_description' => $post->meta_description,
            'snapshot' => $post->only([
                'slug', 'keywords', 'focus_keyword', 'secondary_keywords', 'faq', 'related_slugs',
                'cta_text', 'cta_url', 'cover_image', 'search_intent', 'business_intent',
                'review_status', 'quality_scores', 'meta_title', 'meta_description',
            ]),
            'created_by' => $createdBy,
            'note' => $note,
        ]);
    }

    public function restore(BlogPost $post, BlogPostVersion $version): BlogPost
    {
        $post->fill([
            'title' => $version->title,
            'content' => $version->content,
            'excerpt' => $version->excerpt,
            'meta_title' => $version->meta_title,
            'meta_description' => $version->meta_description,
            ...($version->snapshot ?? []),
        ]);
        $post->save();
        $this->snapshot($post, 'restore-from-v'.$version->version);

        return $post->fresh();
    }
}
