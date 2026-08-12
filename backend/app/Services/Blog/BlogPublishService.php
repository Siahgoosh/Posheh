<?php

namespace App\Services\Blog;

use App\Models\BlogPost;
use App\Services\Blog\BlogQualityGate;
use Illuminate\Support\Str;

class BlogPublishService
{
    public function __construct(
        private readonly BlogQualityGate $gate,
        private readonly BlogReadingTimeCalculator $readingTime,
        private readonly BlogSitemapService $sitemap,
        private readonly BlogAuditLogger $audit,
    ) {}

    /** @param array<string, mixed> $data @return array{ok: bool, post?: BlogPost, gate?: array} */
    public function publish(BlogPost $post, array $data = [], $request = null): array
    {
        $payload = array_merge($post->toArray(), $data, ['is_published' => true]);
        $gate = $this->gate->evaluate($payload, forPublish: true);
        if (! $gate['passed']) {
            return ['ok' => false, 'gate' => $gate];
        }

        $old = $post->only(['is_published', 'review_status', 'published_at', 'robots_directive']);
        $post->fill([
            ...$data,
            'is_published' => true,
            'review_status' => BlogPost::REVIEW_PUBLISHED,
            'published_at' => $post->published_at ?? now(),
            'scheduled_at' => null,
            'robots_directive' => ($data['robots_directive'] ?? null) === 'noindex,nofollow'
                ? 'index,follow'
                : ($data['robots_directive'] ?? $post->robots_directive ?: 'index,follow'),
            'reading_time' => $this->readingTime->calculate($data['content'] ?? $post->content),
            'content_updated_at' => now(),
        ]);
        $post->save();

        $this->sitemap->invalidate();
        $this->audit->log($post, 'article_published', $old, $post->only(['is_published', 'review_status', 'published_at', 'robots_directive']), $request);

        return ['ok' => true, 'post' => $post->fresh(), 'gate' => $gate];
    }

    public function unpublish(BlogPost $post, $request = null): BlogPost
    {
        $old = $post->only(['is_published', 'review_status']);
        $post->update([
            'is_published' => false,
            'review_status' => 'unpublished',
            'published_at' => null,
        ]);
        $this->sitemap->invalidate();
        $this->audit->log($post, 'article_unpublished', $old, $post->only(['is_published', 'review_status']), $request);

        return $post->fresh();
    }

    public function schedule(BlogPost $post, string $at, $request = null): array
    {
        $gate = $this->gate->evaluate($post->toArray(), forPublish: true);
        if (! $gate['passed']) {
            return ['ok' => false, 'gate' => $gate];
        }

        $post->update([
            'is_published' => false,
            'review_status' => 'scheduled',
            'scheduled_at' => $at,
            'published_at' => null,
        ]);
        $this->audit->log($post, 'article_scheduled', null, ['scheduled_at' => $at], $request);

        return ['ok' => true, 'post' => $post->fresh(), 'gate' => $gate];
    }

    public function ensurePreviewToken(BlogPost $post): string
    {
        if (! $post->preview_token) {
            $post->preview_token = Str::random(40);
            $post->save();
        }

        return $post->preview_token;
    }
}
