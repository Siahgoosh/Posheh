<?php

namespace App\Services\BlogImages;

use App\Contracts\Ai\ImageProviderInterface;
use App\Services\Ai\Providers\MockImageProvider;
use App\Services\Ai\Providers\OpenAiImageProvider;

class ImageProviderRegistry
{
    /** @var array<string, ImageProviderInterface> */
    private array $providers;

    public function __construct()
    {
        $this->providers = [
            'mock' => new MockImageProvider,
            'openai' => new OpenAiImageProvider,
        ];
    }

    public function get(?string $key = null): ImageProviderInterface
    {
        $key = $key ?: (string) config('blog_images.default_provider', 'mock');
        $p = $this->providers[$key] ?? null;
        if ($p && $p->isAvailable()) {
            return $p;
        }
        $fallback = (string) config('blog_images.fallback_provider', 'mock');

        return $this->providers[$fallback] ?? $this->providers['mock'];
    }

    /** @return list<array{key:string,available:bool}> */
    public function list(): array
    {
        $out = [];
        foreach ($this->providers as $k => $p) {
            $out[] = ['key' => $k, 'available' => $p->isAvailable()];
        }

        return $out;
    }
}
