<?php

namespace App\Services\Admin;

use App\Models\Office;

class OfficeSeoService
{
    /** @return list<array{path: string, priority: float, lastmod: string|null}> */
    public function sitemapEntries(): array
    {
        return Office::query()
            ->where('is_active', true)
            ->where('plan_active', true)
            ->where('website_status', 'published')
            ->whereNotNull('subdomain')
            ->get(['id', 'slug', 'subdomain', 'updated_at', 'website_published_at'])
            ->flatMap(function (Office $office) {
                $lastmod = ($office->website_published_at ?? $office->updated_at)?->toIso8601String();
                $entries = [
                    [
                        'path' => '/site/'.$office->subdomain,
                        'priority' => 0.82,
                        'lastmod' => $lastmod,
                    ],
                ];
                if ($office->slug) {
                    $entries[] = [
                        'path' => '/o/'.$office->slug,
                        'priority' => 0.78,
                        'lastmod' => $lastmod,
                    ];
                }

                return $entries;
            })
            ->all();
    }
}
