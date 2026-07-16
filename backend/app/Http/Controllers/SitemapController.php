<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Api\Blog\BlogController;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function xml(): Response
    {
        $blog = app(BlogController::class)->sitemap();
        $payload = json_decode($blog->getContent(), true);
        $base = rtrim(config('app.frontend_url', config('app.url')), '/');

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";

        foreach ($payload['static'] ?? [] as $item) {
            $xml .= $this->url($base.$item['path'], $item['priority'] ?? 0.5);
        }

        foreach ($payload['posts'] ?? [] as $post) {
            $xml .= $this->url($base.($post['path'] ?? '/blog/'.$post['slug']), 0.8, $post['updated_at'] ?? null);
        }

        $xml .= '</urlset>';

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    private function url(string $loc, float $priority, ?string $lastmod = null): string
    {
        $lastmodTag = $lastmod ? '<lastmod>'.substr($lastmod, 0, 10).'</lastmod>' : '';

        return "  <url><loc>{$loc}</loc>{$lastmodTag}<changefreq>weekly</changefreq><priority>{$priority}</priority></url>\n";
    }
}
