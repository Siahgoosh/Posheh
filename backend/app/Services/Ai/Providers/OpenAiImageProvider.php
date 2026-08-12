<?php

namespace App\Services\Ai\Providers;

use App\Contracts\Ai\ImageProviderInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * OpenAI Images provider — disabled unless BLOG_IMAGE_OPENAI_ENABLED + key.
 * Never logs API keys. Never auto-publishes images.
 */
class OpenAiImageProvider implements ImageProviderInterface
{
    public function key(): string
    {
        return 'openai';
    }

    public function isAvailable(): bool
    {
        return (bool) config('blog_images.openai_enabled', false) && $this->apiKey() !== '';
    }

    public function generate(array $request): array
    {
        if (! $this->isAvailable()) {
            return [
                'ok' => false,
                'binary' => null,
                'mime' => null,
                'width' => null,
                'height' => null,
                'model' => (string) ($request['model'] ?? config('blog_images.openai_model', 'dall-e-3')),
                'error' => 'OpenAI image provider disabled or missing key',
            ];
        }

        $model = (string) ($request['model'] ?? config('blog_images.openai_model', 'dall-e-3'));
        $size = (string) ($request['resolution'] ?? config('blog_images.default_resolution', '1792x1024'));
        $timeout = (int) ($request['timeout'] ?? 90);

        try {
            $response = Http::withToken($this->apiKey())
                ->timeout($timeout)
                ->post('https://api.openai.com/v1/images/generations', [
                    'model' => $model,
                    'prompt' => (string) ($request['prompt'] ?? ''),
                    'n' => 1,
                    'size' => $size,
                    'quality' => (string) ($request['quality'] ?? 'standard'),
                    'response_format' => 'b64_json',
                ]);

            if (! $response->successful()) {
                Log::warning('blog_images.openai_failed', ['status' => $response->status()]);

                return [
                    'ok' => false,
                    'binary' => null,
                    'mime' => null,
                    'width' => null,
                    'height' => null,
                    'model' => $model,
                    'error' => 'OpenAI images failed (HTTP '.$response->status().')',
                ];
            }

            $b64 = (string) data_get($response->json(), 'data.0.b64_json', '');
            if ($b64 === '') {
                return [
                    'ok' => false,
                    'binary' => null,
                    'mime' => null,
                    'width' => null,
                    'height' => null,
                    'model' => $model,
                    'error' => 'Empty image payload',
                ];
            }

            [$w, $h] = array_map('intval', explode('x', $size) + [0, 0]);

            return [
                'ok' => true,
                'binary' => base64_decode($b64, true) ?: null,
                'mime' => 'image/png',
                'width' => $w ?: null,
                'height' => $h ?: null,
                'model' => $model,
                'cost_toman' => (int) config('blog_images.openai_cost_toman', 0),
            ];
        } catch (\Throwable $e) {
            Log::warning('blog_images.openai_exception', ['message' => $e->getMessage()]);

            return [
                'ok' => false,
                'binary' => null,
                'mime' => null,
                'width' => null,
                'height' => null,
                'model' => $model,
                'error' => 'OpenAI exception: '.$e->getMessage(),
            ];
        }
    }

    private function apiKey(): string
    {
        return trim((string) (
            config('blog_images.openai_key')
            ?: env('BLOG_IMAGE_OPENAI_KEY', '')
            ?: env('OPENAI_API_KEY', '')
        ));
    }
}
