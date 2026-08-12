<?php

namespace App\Services\Blog;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\BlogTag;
use Illuminate\Support\Facades\Cache;

class BlogSitemapService
{
    public function invalidate(): void
    {
        Cache::forget('blog.sitemap.payload.v2');
        Cache::forget('blog.sitemap.xml.v2');
        Cache::forget('blog.sitemap.xml.v3');
        Cache::forget('blog.sitemap.pages.v1');
        Cache::forget('blog.sitemap.posts.v1');
        Cache::forget('blog.sitemap.categories.v1');
        Cache::forget('blog.sitemap.tours.v1');
    }

    /** @return array<string, mixed> */
    public function payload(): array
    {
        return Cache::remember('blog.sitemap.payload.v2', (int) config('blog.sitemap_cache_ttl', 300), function () {
            $posts = BlogPost::published()
                ->where(function ($q) {
                    $q->whereNull('robots_directive')
                        ->orWhere('robots_directive', 'not like', '%noindex%');
                })
                ->orderByDesc('content_updated_at')
                ->orderByDesc('updated_at')
                ->get(['slug', 'updated_at', 'published_at', 'content_updated_at', 'canonical_url', 'category_slug']);

            $posts = $posts->filter(function (BlogPost $post) {
                if (! $post->canonical_url) {
                    return true;
                }
                $canonical = rtrim($post->canonical_url, '/');
                $self = '/blog/'.$post->slug;

                return str_ends_with($canonical, $self) || $canonical === $self;
            })->values();

            $categories = BlogCategory::query()
                ->where('is_active', true)
                ->where('is_indexable', true)
                ->orderBy('sort_order')
                ->get();

            // Fallback to denormalized categories if table empty
            if ($categories->isEmpty()) {
                $categoryRows = BlogPost::published()
                    ->whereNotNull('category_slug')
                    ->selectRaw('category_slug as slug, max(updated_at) as lastmod')
                    ->groupBy('category_slug')
                    ->get();
                $categoryPayload = $categoryRows->map(fn ($row) => [
                    'path' => '/blog/category/'.$row->slug,
                    'priority' => 0.75,
                    'lastmod' => $row->lastmod ? \Illuminate\Support\Carbon::parse($row->lastmod)->toIso8601String() : null,
                ])->all();
            } else {
                $categoryPayload = $categories->map(function (BlogCategory $cat) {
                    $last = BlogPost::published()->where('category_slug', $cat->slug)->max('content_updated_at')
                        ?: BlogPost::published()->where('category_slug', $cat->slug)->max('updated_at');

                    return [
                        'path' => '/blog/category/'.$cat->slug,
                        'priority' => 0.75,
                        'lastmod' => $last ? \Illuminate\Support\Carbon::parse($last)->toIso8601String() : $cat->updated_at?->toIso8601String(),
                    ];
                })->all();
            }

            $tags = BlogTag::query()
                ->where('is_active', true)
                ->where('is_indexable', true)
                ->whereHas('posts', fn ($q) => $q->published())
                ->get()
                ->map(fn (BlogTag $tag) => [
                    'path' => '/blog/tag/'.$tag->slug,
                    'priority' => 0.5,
                    'lastmod' => $tag->updated_at?->toIso8601String(),
                ])->all();

            return [
                'static' => [
                    ['path' => '/', 'priority' => 1.0],
                    ['path' => '/blog', 'priority' => 0.9],
                    ['path' => '/register', 'priority' => 0.9],
                    ['path' => '/download', 'priority' => 0.8],
                    ['path' => '/contact', 'priority' => 0.7],
                    ['path' => '/privacy', 'priority' => 0.5],
                    ['path' => '/terms', 'priority' => 0.5],
                ],
                'categories' => $categoryPayload,
                'tags' => $tags,
                'posts' => $posts->map(fn (BlogPost $post) => [
                    'path' => '/blog/'.$post->slug,
                    'slug' => $post->slug,
                    'updated_at' => ($post->content_updated_at ?? $post->updated_at ?? $post->published_at)?->toIso8601String(),
                ])->all(),
            ];
        });
    }
}
