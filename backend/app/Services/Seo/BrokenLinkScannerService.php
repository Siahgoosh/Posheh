<?php

namespace App\Services\Seo;

use App\Models\BlogBrokenLink;
use App\Models\BlogPost;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

class BrokenLinkScannerService
{
    /**
     * Scan internal/external links in published post HTML.
     *
     * @return array{checked: int, broken: int, stored: int}
     */
    public function scan(int $limitPosts = 100, bool $probeExternal = false): array
    {
        $posts = BlogPost::published()
            ->orderByDesc('updated_at')
            ->limit($limitPosts)
            ->get(['id', 'slug', 'content']);

        $checked = 0;
        $broken = 0;
        $stored = 0;
        $base = rtrim((string) config('app.frontend_url', config('app.url')), '/');

        foreach ($posts as $post) {
            if (! preg_match_all('/<a[^>]+href=["\']([^"\']+)["\'][^>]*>(.*?)<\/a>/is', (string) $post->content, $m, PREG_SET_ORDER)) {
                continue;
            }
            foreach ($m as $match) {
                $href = trim(html_entity_decode($match[1]));
                $anchor = trim(strip_tags($match[2]));
                if ($href === '' || str_starts_with($href, '#') || str_starts_with($href, 'mailto:') || str_starts_with($href, 'tel:')) {
                    continue;
                }
                $checked++;
                $status = null;
                $isInternal = str_starts_with($href, '/') || str_contains($href, parse_url($base, PHP_URL_HOST) ?: 'posheapp.ir');

                if ($isInternal) {
                    $path = parse_url($href, PHP_URL_PATH) ?: $href;
                    if (preg_match('#^/blog/([^/]+)/?$#', $path, $sm)) {
                        $exists = BlogPost::published()->where('slug', $sm[1])->exists();
                        $status = $exists ? 200 : 404;
                    } else {
                        $status = null; // UNKNOWN without live probe
                    }
                } elseif ($probeExternal) {
                    try {
                        $res = Http::timeout(5)->withOptions(['allow_redirects' => false])->head($href);
                        $status = $res->status();
                    } catch (\Throwable) {
                        $status = null;
                    }
                }

                if ($status !== null && ($status >= 400 || $status === 0)) {
                    $broken++;
                    if (Schema::hasTable('blog_broken_links')) {
                        BlogBrokenLink::updateOrCreate(
                            [
                                'blog_post_id' => $post->id,
                                'url' => mb_substr($href, 0, 1000),
                            ],
                            [
                                'anchor' => mb_substr($anchor, 0, 500),
                                'status_code' => $status,
                                'last_checked_at' => now(),
                                'is_resolved' => false,
                            ]
                        );
                        $stored++;
                    }
                }
            }
        }

        return compact('checked', 'broken', 'stored');
    }
}
