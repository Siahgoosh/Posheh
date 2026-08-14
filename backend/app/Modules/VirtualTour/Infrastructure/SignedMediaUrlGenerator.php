<?php

namespace App\Modules\VirtualTour\Infrastructure;

use Illuminate\Support\Facades\URL;

class SignedMediaUrlGenerator
{
    public function isEnabled(): bool
    {
        return (bool) config('virtual-tour.signed_urls', false);
    }

    public function ttlMinutes(): int
    {
        return (int) config('virtual-tour.signed_url_ttl_minutes', 120);
    }

    public function url(string $storagePath): string
    {
        if (! $this->isEnabled()) {
            return '/storage/'.$storagePath;
        }

        if (str_starts_with($storagePath, 'http') || str_starts_with($storagePath, 'demo/')) {
            return str_starts_with($storagePath, 'demo/')
                ? '/demo/'.basename($storagePath)
                : $storagePath;
        }

        return URL::temporarySignedRoute(
            'virtual-tour.media',
            now()->addMinutes($this->ttlMinutes()),
            ['path' => $storagePath],
        );
    }
}
