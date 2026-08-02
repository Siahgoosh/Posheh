<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Api\Blog\BlogController;
use App\Models\BlogPost;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    private function baseUrl(): string
    {
        return rtrim(config('app.frontend_url', config('app.url')), '/');
    }

    /** Sitemap index for Google Search Console */
    public function index(): Response
    {
        $base = $this->baseUrl();
        $now = now()->toAtomString();

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
        $xml .= $this->sitemapEntry($base.'/sitemap-pages.xml', $now);
        $xml .= $this->sitemapEntry($base.'/sitemap-blog.xml', $now);
        $xml .= '</sitemapindex>';

        return $this->xmlResponse($xml);
    }

    /** Static pages + blog categories */
    public function pages(): Response
    {
        $blog = app(BlogController::class)->sitemap();
        $payload = json_decode($blog->getContent(), true);
        $base = $this->baseUrl();

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";

        foreach ($payload['static'] ?? [] as $item) {
            $xml .= $this->url($base.$item['path'], $item['priority'] ?? 0.5, $item['lastmod'] ?? null);
        }

        foreach ($payload['categories'] ?? [] as $cat) {
            $xml .= $this->url($base.$cat['path'], $cat['priority'] ?? 0.75, $cat['lastmod'] ?? null);
        }

        $xml .= $this->url($base.'/blog', 0.9, now()->toIso8601String());
        $xml .= '</urlset>';

        return $this->xmlResponse($xml);
    }

    /** Blog articles only — submit this URL to Google Search Console */
    public function blog(): Response
    {
        $base = $this->baseUrl();
        $posts = BlogPost::published()
            ->orderByDesc('published_at')
            ->get(['slug', 'title', 'cover_image', 'updated_at', 'published_at']);

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"';
        $xml .= ' xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">'."\n";

        foreach ($posts as $post) {
            $loc = $base.'/blog/'.$post->slug;
            $lastmod = ($post->updated_at ?? $post->published_at)?->toIso8601String();
            $escaped = htmlspecialchars($loc, ENT_XML1);
            $lastmodTag = $lastmod ? '<lastmod>'.substr($lastmod, 0, 10).'</lastmod>' : '';

            $xml .= "  <url><loc>{$escaped}</loc>{$lastmodTag}<changefreq>weekly</changefreq><priority>0.8</priority>\n";

            $imageUrl = $this->absoluteImageUrl($post->cover_image, $base);
            if ($imageUrl) {
                $xml .= "    <image:image>\n";
                $xml .= '      <image:loc>'.htmlspecialchars($imageUrl, ENT_XML1)."</image:loc>\n";
                $xml .= '      <image:title>'.htmlspecialchars($post->title, ENT_XML1)."</image:title>\n";
                $xml .= "    </image:image>\n";
            }

            $xml .= "  </url>\n";
        }

        $xml .= '</urlset>';

        return $this->xmlResponse($xml);
    }

    /** @deprecated Use index() — kept for backward compatibility */
    public function xml(): Response
    {
        return $this->index();
    }

    private function sitemapEntry(string $loc, string $lastmod): string
    {
        $escaped = htmlspecialchars($loc, ENT_XML1);

        return "  <sitemap><loc>{$escaped}</loc><lastmod>".substr($lastmod, 0, 10)."</lastmod></sitemap>\n";
    }

    private function url(string $loc, float $priority, ?string $lastmod = null): string
    {
        $escaped = htmlspecialchars($loc, ENT_XML1);
        $lastmodTag = $lastmod ? '<lastmod>'.substr($lastmod, 0, 10).'</lastmod>' : '';

        return "  <url><loc>{$escaped}</loc>{$lastmodTag}<changefreq>weekly</changefreq><priority>{$priority}</priority></url>\n";
    }

    private function absoluteImageUrl(?string $cover, string $base): ?string
    {
        if (! $cover) {
            return $base.'/og-default.png';
        }

        if (str_starts_with($cover, 'http://') || str_starts_with($cover, 'https://')) {
            return $cover;
        }

        return $base.'/'.ltrim($cover, '/');
    }

    private function xmlResponse(string $xml): Response
    {
        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
