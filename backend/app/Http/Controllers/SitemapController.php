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
            $xml .= $this->url($base.$item['path'], $item['priority'] ?? 0.5, $item['lastmod'] ?? null);
        }

        foreach ($payload['categories'] ?? [] as $cat) {
            $xml .= $this->url($base.$cat['path'], $cat['priority'] ?? 0.75, $cat['lastmod'] ?? null);
        }

        foreach ($payload['posts'] ?? [] as $post) {
            $xml .= $this->url($base.($post['path'] ?? '/blog/'.$post['slug']), 0.8, $post['updated_at'] ?? null);
        }

        $tourSeo = app(\App\Modules\VirtualTour\Application\Services\TourSeoService::class);
        foreach ($tourSeo->sitemapEntries() as $entry) {
            $xml .= $this->url($base.$entry['path'], $entry['priority'] ?? 0.75, $entry['lastmod'] ?? null);
        }

        $officeSeo = app(\App\Services\Admin\OfficeSeoService::class);
        foreach ($officeSeo->sitemapEntries() as $entry) {
            $xml .= $this->url($base.$entry['path'], $entry['priority'] ?? 0.75, $entry['lastmod'] ?? null);
        }

        $xml .= '</urlset>';

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    private function url(string $loc, float $priority, ?string $lastmod = null): string
    {
        $escaped = htmlspecialchars($loc, ENT_XML1);
        $lastmodTag = $lastmod ? '<lastmod>'.substr($lastmod, 0, 10).'</lastmod>' : '';

        return "  <url><loc>{$escaped}</loc>{$lastmodTag}<changefreq>weekly</changefreq><priority>{$priority}</priority></url>\n";
    }
}
