<?php

namespace App\Http\Controllers;

use App\Services\Blog\BlogSitemapService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class SitemapController extends Controller
{
    public function __construct(
        private readonly BlogSitemapService $sitemapService,
    ) {}

    public function xml(): Response
    {
        $base = rtrim(config('app.frontend_url', config('app.url')), '/');
        $xml = Cache::remember('blog.sitemap.xml.v2', (int) config('blog.sitemap_cache_ttl', 300), function () use ($base) {
            $payload = $this->sitemapService->payload();
            $out = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
            $out .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
            $out .= $this->sitemapEntry($base.'/sitemap-pages.xml');
            $out .= $this->sitemapEntry($base.'/sitemap-posts.xml');
            $out .= $this->sitemapEntry($base.'/sitemap-categories.xml');
            $out .= '</sitemapindex>';

            // Also embed note: index points to child maps; keep payload for children
            return $out;
        });

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    public function pages(): Response
    {
        $payload = $this->sitemapService->payload();
        $base = rtrim(config('app.frontend_url', config('app.url')), '/');
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
        foreach ($payload['static'] ?? [] as $item) {
            $xml .= $this->url($base.$item['path'], $item['priority'] ?? 0.5, $item['lastmod'] ?? null);
        }
        $xml .= '</urlset>';

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    public function posts(): Response
    {
        $payload = $this->sitemapService->payload();
        $base = rtrim(config('app.frontend_url', config('app.url')), '/');
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
        foreach ($payload['posts'] ?? [] as $post) {
            $xml .= $this->url($base.($post['path'] ?? '/blog/'.$post['slug']), 0.8, $post['updated_at'] ?? null);
        }
        $xml .= '</urlset>';

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    public function categories(): Response
    {
        $payload = $this->sitemapService->payload();
        $base = rtrim(config('app.frontend_url', config('app.url')), '/');
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
        foreach ($payload['categories'] ?? [] as $cat) {
            $xml .= $this->url($base.$cat['path'], $cat['priority'] ?? 0.75, $cat['lastmod'] ?? null);
        }
        foreach ($payload['tags'] ?? [] as $tag) {
            $xml .= $this->url($base.$tag['path'], $tag['priority'] ?? 0.5, $tag['lastmod'] ?? null);
        }
        $xml .= '</urlset>';

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    private function sitemapEntry(string $loc): string
    {
        return '  <sitemap><loc>'.htmlspecialchars($loc, ENT_XML1).'</loc></sitemap>'."\n";
    }

    private function url(string $loc, float $priority, ?string $lastmod = null): string
    {
        $escaped = htmlspecialchars($loc, ENT_XML1);
        $lastmodTag = $lastmod ? '<lastmod>'.substr($lastmod, 0, 10).'</lastmod>' : '';

        return "  <url><loc>{$escaped}</loc>{$lastmodTag}<priority>{$priority}</priority></url>\n";
    }
}
