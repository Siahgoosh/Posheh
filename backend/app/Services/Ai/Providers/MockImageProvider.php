<?php

namespace App\Services\Ai\Providers;

use App\Contracts\Ai\ImageProviderInterface;

/**
 * Deterministic SVG placeholder — never claims to be a real property photo.
 * Used for dry-run/dev and when no external provider is configured.
 */
class MockImageProvider implements ImageProviderInterface
{
    public function key(): string
    {
        return 'mock';
    }

    public function isAvailable(): bool
    {
        return true;
    }

    public function generate(array $request): array
    {
        $title = mb_substr(preg_replace('/[^\p{L}\p{N}\s\-]/u', '', (string) ($request['prompt'] ?? 'Posheh')) ?? 'Posheh', 0, 60);
        $w = 1200;
        $h = 630;
        $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="{$w}" height="{$h}" viewBox="0 0 {$w} {$h}">
  <defs>
    <linearGradient id="g" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0%" stop-color="#0f766e"/>
      <stop offset="100%" stop-color="#134e4a"/>
    </linearGradient>
  </defs>
  <rect width="100%" height="100%" fill="url(#g)"/>
  <rect x="48" y="48" width="1104" height="534" rx="24" fill="rgba(255,255,255,0.08)"/>
  <text x="80" y="300" fill="#ecfdf5" font-family="Tahoma,sans-serif" font-size="36">پوشه · Illustrative</text>
  <text x="80" y="360" fill="#a7f3d0" font-family="Tahoma,sans-serif" font-size="22">{$title}</text>
  <text x="80" y="520" fill="#99f6e4" font-family="Tahoma,sans-serif" font-size="16">Mock provider — not a real property photo</text>
</svg>
SVG;

        return [
            'ok' => true,
            'binary' => $svg,
            'mime' => 'image/svg+xml',
            'width' => $w,
            'height' => $h,
            'model' => 'mock-svg-v1',
            'cost_toman' => 0,
        ];
    }
}
