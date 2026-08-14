<?php

namespace App\Services\Ai\Providers;

use App\Contracts\Ai\AiProviderInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * OpenAI provider stub — disabled unless CONTENT_AI_OPENAI_KEY (or OPENAI_API_KEY) is set.
 * Never logs API keys. Never auto-publishes.
 */
class OpenAiProvider implements AiProviderInterface
{
    public function key(): string
    {
        return 'openai';
    }

    public function isAvailable(): bool
    {
        $key = $this->apiKey();

        return $key !== '' && (bool) config('content_ops.openai_enabled', false);
    }

    public function complete(array $request): array
    {
        if (! $this->isAvailable()) {
            return [
                'text' => '',
                'prompt_tokens' => 0,
                'completion_tokens' => 0,
                'model' => (string) ($request['model'] ?? 'gpt-4o-mini'),
                'error' => 'OpenAI provider disabled or missing key',
            ];
        }

        $model = (string) ($request['model'] ?? config('content_ops.openai_model', 'gpt-4o-mini'));
        $timeout = (int) ($request['timeout'] ?? 60);
        $messages = [];
        if (! empty($request['system'])) {
            $messages[] = ['role' => 'system', 'content' => (string) $request['system']];
        }
        $messages[] = ['role' => 'user', 'content' => (string) ($request['user'] ?? '')];

        try {
            $response = Http::withToken($this->apiKey())
                ->timeout($timeout)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => $model,
                    'temperature' => (float) ($request['temperature'] ?? 0.3),
                    'max_tokens' => (int) ($request['max_tokens'] ?? 2000),
                    'messages' => $messages,
                ]);

            if (! $response->successful()) {
                Log::warning('content_ops.openai_failed', ['status' => $response->status()]);

                return [
                    'text' => '',
                    'prompt_tokens' => 0,
                    'completion_tokens' => 0,
                    'model' => $model,
                    'error' => 'OpenAI request failed (status '.$response->status().')',
                ];
            }

            $json = $response->json();
            $text = (string) data_get($json, 'choices.0.message.content', '');
            $usage = data_get($json, 'usage', []);

            return [
                'text' => $text,
                'prompt_tokens' => (int) ($usage['prompt_tokens'] ?? 0),
                'completion_tokens' => (int) ($usage['completion_tokens'] ?? 0),
                'model' => $model,
                'raw' => ['id' => data_get($json, 'id')],
            ];
        } catch (\Throwable $e) {
            Log::warning('content_ops.openai_exception', ['message' => $e->getMessage()]);

            return [
                'text' => '',
                'prompt_tokens' => 0,
                'completion_tokens' => 0,
                'model' => $model,
                'error' => 'OpenAI exception: '.$e->getMessage(),
            ];
        }
    }

    private function apiKey(): string
    {
        return trim((string) (
            config('content_ops.openai_key')
            ?: env('CONTENT_AI_OPENAI_KEY', '')
            ?: env('OPENAI_API_KEY', '')
        ));
    }
}
