<?php

namespace App\Services\Seo;

use App\Models\BlogPost;
use App\Services\Blog\BlogSitemapService;

class SitemapValidatorService
{
    public const MAX_URLS_PER_SITEMAP = 50000;

    public function __construct(
        private readonly BlogSitemapService $sitemap,
    ) {}

    /**
     * @return array{issues: list<array<string,mixed>>, metrics: array<string,mixed>}
     */
    public function validate(): array
    {
        $payload = $this->sitemap->payload();
        $issues = [];
        $seen = [];
        $urls = [];

        foreach (['static', 'categories', 'tags', 'posts'] as $bucket) {
            foreach ($payload[$bucket] ?? [] as $item) {
                $path = $item['path'] ?? (isset($item['slug']) ? '/blog/'.$item['slug'] : null);
                if (! $path) {
                    $issues[] = [
                        'severity' => 'high',
                        'category' => 'sitemap',
                        'message' => 'Invalid sitemap entry missing path',
                        'url' => (string) ($item['slug'] ?? 'unknown'),
                    ];
                    continue;
                }
                if (isset($seen[$path])) {
                    $issues[] = [
                        'severity' => 'high',
                        'category' => 'sitemap',
                        'message' => 'Duplicate URL in sitemap payload',
                        'url' => $path,
                    ];
                }
                $seen[$path] = true;
                $urls[] = $path;
            }
        }

        // Noindex published posts must not appear
        $noindexInSitemap = 0;
        foreach ($payload['posts'] ?? [] as $post) {
            $slug = $post['slug'] ?? null;
            if (! $slug) {
                continue;
            }
            $model = BlogPost::query()->where('slug', $slug)->first(['robots_directive', 'canonical_url', 'is_published']);
            if (! $model) {
                $issues[] = [
                    'severity' => 'critical',
                    'category' => 'sitemap',
                    'message' => 'Sitemap URL has no matching post',
                    'url' => '/blog/'.$slug,
                ];
                continue;
            }
            if (! $model->is_published) {
                $issues[] = [
                    'severity' => 'critical',
                    'category' => 'sitemap',
                    'message' => 'Unpublished post in sitemap',
                    'url' => '/blog/'.$slug,
                ];
            }
            if (str_contains(strtolower((string) $model->robots_directive), 'noindex')) {
                $noindexInSitemap++;
                $issues[] = [
                    'severity' => 'critical',
                    'category' => 'sitemap',
                    'message' => 'Noindex URL present in sitemap',
                    'url' => '/blog/'.$slug,
                ];
            }
            if ($model->canonical_url) {
                $self = '/blog/'.$slug;
                $c = rtrim($model->canonical_url, '/');
                if (! str_ends_with($c, $self) && $c !== $self) {
                    $issues[] = [
                        'severity' => 'high',
                        'category' => 'sitemap',
                        'message' => 'Sitemap URL ≠ Canonical (should be filtered)',
                        'url' => $self,
                        'meta' => ['canonical' => $model->canonical_url],
                    ];
                }
            }
        }

        $count = count($urls);
        if ($count > self::MAX_URLS_PER_SITEMAP) {
            $issues[] = [
                'severity' => 'high',
                'category' => 'sitemap',
                'message' => 'Sitemap URL count exceeds 50k — split required',
                'url' => '/sitemap-posts.xml',
                'meta' => ['count' => $count],
            ];
        }

        return [
            'issues' => $issues,
            'metrics' => [
                'url_count' => $count,
                'unique' => count($seen),
                'noindex_in_sitemap' => $noindexInSitemap,
                'max_urls' => self::MAX_URLS_PER_SITEMAP,
                'split_needed' => $count > self::MAX_URLS_PER_SITEMAP,
            ],
        ];
    }
}
