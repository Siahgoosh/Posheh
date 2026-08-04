<?php

namespace App\Modules\VirtualTour\Application\Services;

use App\Models\VirtualTour;
use App\Modules\VirtualTour\Application\Contracts\PanoramaStorageInterface;
use Illuminate\Support\Str;

class TourSeoService
{
    public function __construct(
        private readonly PanoramaStorageInterface $storage,
    ) {}

    public function metaForTour(VirtualTour $tour): array
    {
        $tour->loadMissing(['scenes', 'property', 'office']);
        $base = rtrim(config('app.frontend_url', config('app.url')), '/');
        $url = "{$base}/tour/{$tour->slug}";
        $image = $this->coverImageUrl($tour);
        $description = Str::limit($tour->description ?? $this->defaultDescription($tour), 160);
        $isPublic = $tour->status === 'published'
            && $tour->visibility === 'public'
            && ! $tour->archived_at
            && ! $tour->access_password;

        return [
            'title' => $tour->title,
            'description' => $description,
            'canonical' => $url,
            'og_image' => $image,
            'og_type' => 'website',
            'noindex' => ! $isPublic,
            'json_ld' => $this->jsonLd($tour, $url, $image),
            'scene_urls' => $tour->scenes->map(fn ($s) => [
                'scene_id' => $s->id,
                'name' => $s->name,
                'url' => "{$base}/tour/{$tour->slug}/scene/{$s->id}",
            ]),
        ];
    }

    public function jsonLd(VirtualTour $tour, string $url, string $image): array
    {
        $property = $tour->property;
        $schemas = [];

        $schemas[] = [
            '@context' => 'https://schema.org',
            '@type' => 'WebPage',
            'name' => $tour->title,
            'description' => $tour->description,
            'url' => $url,
            'image' => $image,
            'inLanguage' => 'fa-IR',
        ];

        if ($property) {
            $schemas[] = [
                '@context' => 'https://schema.org',
                '@type' => 'RealEstateListing',
                'name' => $tour->title,
                'description' => $tour->description,
                'url' => $url,
                'image' => $image,
                'floorSize' => $property->area ? [
                    '@type' => 'QuantitativeValue',
                    'value' => $property->area,
                    'unitCode' => 'MTK',
                ] : null,
                'offers' => $property->price ? [
                    '@type' => 'Offer',
                    'price' => $property->price,
                    'priceCurrency' => 'IRR',
                ] : null,
                'address' => [
                    '@type' => 'PostalAddress',
                    'addressLocality' => $property->city,
                    'addressRegion' => $property->district,
                ],
            ];
        }

        $schemas[] = [
            '@context' => 'https://schema.org',
            '@type' => 'VirtualTour',
            'name' => $tour->title,
            'url' => $url,
            'image' => $image,
            'tourType' => $tour->tour_type ?? 'panorama_360',
        ];

        return $schemas;
    }

    /** @return list<array{path: string, priority: float, lastmod: string|null}> */
    public function sitemapEntries(): array
    {
        return VirtualTour::query()
            ->where('status', 'published')
            ->where('visibility', 'public')
            ->whereNull('archived_at')
            ->whereNull('access_password')
            ->with(['scenes' => fn ($q) => $q->where('is_visible', true)])
            ->get(['id', 'slug', 'updated_at'])
            ->flatMap(function (VirtualTour $tour) {
                $base = '/tour/'.$tour->slug;
                $lastmod = $tour->updated_at?->toIso8601String();
                $entries = [
                    ['path' => $base, 'priority' => 0.85, 'lastmod' => $lastmod],
                ];
                foreach ($tour->scenes as $scene) {
                    $entries[] = [
                        'path' => "{$base}/scene/{$scene->id}",
                        'priority' => 0.7,
                        'lastmod' => $lastmod,
                    ];
                }
                return $entries;
            })
            ->all();
    }

    private function coverImageUrl(VirtualTour $tour): string
    {
        $scene = $tour->scenes->firstWhere('is_default', true) ?? $tour->scenes->first();
        if ($scene?->thumbnail_path) {
            return $this->storage->url($scene->thumbnail_path);
        }
        $base = rtrim(config('app.frontend_url', config('app.url')), '/');

        return "{$base}/favicon.svg";
    }

    private function defaultDescription(VirtualTour $tour): string
    {
        $type = $tour->tour_type === 'smart_walk' ? 'تور مجازی Smart Walk' : 'تور مجازی ۳۶۰ درجه';

        return "{$type} — {$tour->title}";
    }
}
