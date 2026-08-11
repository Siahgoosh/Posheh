<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Api\Blog\BlogController;
use App\Modules\VirtualTour\Application\Services\TourSeoService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Schema;

class SitemapController extends Controller
{
    private function baseUrl(): string
    {
        return rtrim((string) config('app.frontend_url', config('app.url')), '/');
    }

    /** @return array{static: list<array>, categories: list<array>, posts: list<array>} */
    private function blogPayload(): array
    {
        $blog = app(BlogController::class)->sitemap();
        $payload = json_decode($blog->getContent(), true) ?: [];

        return [
            'static' => $payload['static'] ?? [],
            'categories' => $payload['categories'] ?? [],
            'posts' => $payload['posts'] ?? [],
        ];
    }

    /** Sitemap index — submit this URL in Google Search Console. */
    public function index(): Response
    {
        $base = $this->baseUrl();
        $today = date('Y-m-d');
        $children = [
            "{$base}/sitemap-pages.xml",
            "{$base}/sitemap-blog.xml",
            "{$base}/sitemap-tours.xml",
        ];

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
        foreach ($children as $loc) {
            $escaped = htmlspecialchars($loc, ENT_XML1);
            $xml .= "  <sitemap><loc>{$escaped}</loc><lastmod>{$today}</lastmod></sitemap>\n";
        }
        $xml .= '</sitemapindex>';

        return $this->xmlResponse($xml);
    }

    /** Backward-compatible full urlset (also linked from robots). */
    public function xml(): Response
    {
        $payload = $this->blogPayload();
        $base = $this->baseUrl();

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";

        foreach ($payload['static'] as $item) {
            $xml .= $this->url($base.$item['path'], (float) ($item['priority'] ?? 0.5), $item['lastmod'] ?? null);
        }
        foreach ($payload['categories'] as $cat) {
            $xml .= $this->url($base.$cat['path'], (float) ($cat['priority'] ?? 0.75), $cat['lastmod'] ?? null);
        }
        foreach ($payload['posts'] as $post) {
            $xml .= $this->url($base.($post['path'] ?? '/blog/'.$post['slug']), 0.8, $post['updated_at'] ?? null);
        }
        foreach ($this->tourEntries() as $entry) {
            $xml .= $this->url($base.$entry['path'], (float) $entry['priority'], $entry['lastmod'] ?? null);
        }

        $xml .= '</urlset>';

        return $this->xmlResponse($xml);
    }

    public function pages(): Response
    {
        $base = $this->baseUrl();
        $payload = $this->blogPayload();

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
        foreach ($payload['static'] as $item) {
            $xml .= $this->url($base.$item['path'], (float) ($item['priority'] ?? 0.5), $item['lastmod'] ?? null);
        }
        $xml .= '</urlset>';

        return $this->xmlResponse($xml);
    }

    public function blog(): Response
    {
        $base = $this->baseUrl();
        $payload = $this->blogPayload();

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
        $xml .= $this->url($base.'/blog', 0.9, null);
        foreach ($payload['categories'] as $cat) {
            $xml .= $this->url($base.$cat['path'], (float) ($cat['priority'] ?? 0.75), $cat['lastmod'] ?? null);
        }
        foreach ($payload['posts'] as $post) {
            $xml .= $this->url($base.($post['path'] ?? '/blog/'.$post['slug']), 0.8, $post['updated_at'] ?? null);
        }
        $xml .= '</urlset>';

        return $this->xmlResponse($xml);
    }

    public function tours(): Response
    {
        $base = $this->baseUrl();
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
        foreach ($this->tourEntries() as $entry) {
            $xml .= $this->url($base.$entry['path'], (float) $entry['priority'], $entry['lastmod'] ?? null);
        }
        $xml .= '</urlset>';

        return $this->xmlResponse($xml);
    }

    /** @return list<array{path: string, priority: float, lastmod: string|null}> */
    private function tourEntries(): array
    {
        if (! class_exists(TourSeoService::class)) {
            return [];
        }
        if (! Schema::hasTable('virtual_tours')) {
            return [];
        }

        try {
            return app(TourSeoService::class)->sitemapEntries();
        } catch (\Throwable) {
            return [];
        }
    }

    private function xmlResponse(string $xml): Response
    {
        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=300',
            'X-Robots-Tag' => 'noindex',
        ]);
    }

    private function url(string $loc, float $priority, ?string $lastmod = null): string
    {
        $escaped = htmlspecialchars($loc, ENT_XML1);
        $lastmodTag = $lastmod ? '<lastmod>'.substr($lastmod, 0, 10).'</lastmod>' : '';

        return "  <url><loc>{$escaped}</loc>{$lastmodTag}<changefreq>weekly</changefreq><priority>{$priority}</priority></url>\n";
    }
}
