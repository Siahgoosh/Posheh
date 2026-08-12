<?php

namespace App\Services\Blog;

use App\Models\BlogPost;
use App\Services\ContentOps\FactCheckEngine;
use Illuminate\Support\Str;

class BlogPublishService
{
    public function __construct(
        private readonly BlogQualityGate $gate,
        private readonly BlogReadingTimeCalculator $readingTime,
        private readonly BlogSitemapService $sitemap,
        private readonly BlogAuditLogger $audit,
        private readonly FactCheckEngine $facts,
    ) {}

    /** @param array<string, mixed> $data @return array{ok: bool, post?: BlogPost, gate?: array, ops_blockers?: list<string>} */
    public function publish(BlogPost $post, array $data = [], $request = null): array
    {
        $payload = array_merge($post->toArray(), $data, ['is_published' => true]);
        $gate = $this->gate->evaluate($payload, forPublish: true);
        if (! $gate['passed']) {
            return ['ok' => false, 'gate' => $gate];
        }

        $opsBlockers = [];
        if ($this->facts->hasBlockingClaims($post->id)) {
            $opsBlockers[] = 'Unresolved sensitive claims — human fact review required';
        }
        if ($opsBlockers !== []) {
            return ['ok' => false, 'gate' => $gate, 'ops_blockers' => $opsBlockers];
        }

        $old = $post->only(['is_published', 'review_status', 'published_at', 'robots_directive']);
        $fill = [
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
        ];
        if (\Illuminate\Support\Facades\Schema::hasColumn('blog_posts', 'ops_status')) {
            $fill['ops_status'] = 'PUBLISHED';
            $fill['freshness_class'] = 'fresh';
        }
        $post->fill($fill);
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
        if ($this->facts->hasBlockingClaims($post->id)) {
            return [
                'ok' => false,
                'gate' => $gate,
                'ops_blockers' => ['Unresolved sensitive claims — cannot schedule until fact review'],
            ];
        }

        $update = [
            'is_published' => false,
            'review_status' => 'scheduled',
            'scheduled_at' => $at,
            'published_at' => null,
        ];
        if (\Illuminate\Support\Facades\Schema::hasColumn('blog_posts', 'ops_status')) {
            $update['ops_status'] = 'SCHEDULED';
        }
        $post->update($update);
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
