<?php

namespace App\Http\Controllers;

use App\Modules\VirtualTour\Application\Services\TourSeoService;
use App\Services\Blog\BlogSitemapService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class SitemapController extends Controller
{
    public function __construct(
        private readonly BlogSitemapService $sitemapService,
    ) {}

    private function baseUrl(): string
    {
        return rtrim((string) config('app.frontend_url', config('app.url')), '/');
    }

    /** Sitemap index — submit this URL in Google Search Console. */
    public function xml(): Response
    {
        $base = $this->baseUrl();
        $xml = Cache::remember('blog.sitemap.xml.v3', (int) config('blog.sitemap_cache_ttl', 300), function () use ($base) {
            $out = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
            $out .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
            $out .= $this->sitemapEntry($base.'/sitemap-pages.xml');
            $out .= $this->sitemapEntry($base.'/sitemap-posts.xml');
            $out .= $this->sitemapEntry($base.'/sitemap-categories.xml');
            $out .= $this->sitemapEntry($base.'/sitemap-locations.xml');
            $out .= $this->sitemapEntry($base.'/sitemap-tours.xml');
            $out .= '</sitemapindex>';

            return $out;
        });

        return $this->xmlResponse($xml);
    }

    /** Backward-compatible alias for older Search Console submissions. */
    public function index(): Response
    {
        return $this->xml();
    }

    public function pages(): Response
    {
        $payload = $this->sitemapService->payload();
        $base = $this->baseUrl();
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
        foreach ($payload['static'] ?? [] as $item) {
            $xml .= $this->url($base.$item['path'], (float) ($item['priority'] ?? 0.5), $item['lastmod'] ?? null);
        }
        $xml .= '</urlset>';

        return $this->xmlResponse($xml);
    }

    public function posts(): Response
    {
        $payload = $this->sitemapService->payload();
        $base = $this->baseUrl();
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
        foreach ($payload['posts'] ?? [] as $post) {
            $xml .= $this->url($base.($post['path'] ?? '/blog/'.$post['slug']), 0.8, $post['updated_at'] ?? null);
        }
        $xml .= '</urlset>';

        return $this->xmlResponse($xml);
    }

    /** Alias used by older robots.txt / GSC entries. */
    public function blog(): Response
    {
        return $this->posts();
    }

    public function categories(): Response
    {
        $payload = $this->sitemapService->payload();
        $base = $this->baseUrl();
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
        foreach ($payload['categories'] ?? [] as $cat) {
            $xml .= $this->url($base.$cat['path'], (float) ($cat['priority'] ?? 0.75), $cat['lastmod'] ?? null);
        }
        foreach ($payload['tags'] ?? [] as $tag) {
            $xml .= $this->url($base.$tag['path'], (float) ($tag['priority'] ?? 0.5), $tag['lastmod'] ?? null);
        }
        $xml .= '</urlset>';

        return $this->xmlResponse($xml);
    }

    public function locations(): Response
    {
        $payload = $this->sitemapService->payload();
        $base = $this->baseUrl();
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
        foreach ($payload['locations'] ?? [] as $loc) {
            $xml .= $this->url($base.$loc['path'], (float) ($loc['priority'] ?? 0.7), $loc['lastmod'] ?? null);
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

    private function sitemapEntry(string $loc): string
    {
        return '  <sitemap><loc>'.htmlspecialchars($loc, ENT_XML1).'</loc></sitemap>'."\n";
    }

    private function url(string $loc, float $priority, ?string $lastmod = null): string
    {
        $escaped = htmlspecialchars($loc, ENT_XML1);
        $lastmodTag = $lastmod ? '<lastmod>'.substr($lastmod, 0, 10).'</lastmod>' : '';

        return "  <url><loc>{$escaped}</loc>{$lastmodTag}<changefreq>weekly</changefreq><priority>{$priority}</priority></url>\n";
    }
}
