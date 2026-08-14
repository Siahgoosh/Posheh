<?php

namespace App\Services\Ai\Providers;

use App\Contracts\Ai\AiProviderInterface;
use App\Services\Blog\BlogAiAssistantService;

/**
 * Local heuristic provider — wraps Content OS tasks onto BlogAiAssistant heuristics.
 * External LLM stays optional.
 */
class LocalHeuristicAiProvider implements AiProviderInterface
{
    public function __construct(
        private readonly BlogAiAssistantService $assistant,
    ) {}

    public function key(): string
    {
        return 'local';
    }

    public function isAvailable(): bool
    {
        return true;
    }

    public function complete(array $request): array
    {
        $user = (string) ($request['user'] ?? '');
        $payload = [];
        if ($user !== '' && str_starts_with(ltrim($user), '{')) {
            $decoded = json_decode($user, true);
            if (is_array($decoded)) {
                $payload = $decoded;
            }
        }
        if ($payload === []) {
            $payload = [
                'title' => mb_substr(strip_tags($user), 0, 120),
                'content' => $user,
            ];
        }

        $action = (string) ($request['task'] ?? $payload['action'] ?? 'brief');
        if (! in_array($action, $this->assistant->availableActions(), true)) {
            $action = 'brief';
        }

        $result = $this->assistant->assist($action, $payload);
        $text = json_encode($result['result'] ?? $result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) ?: '{}';
        $promptTokens = max(1, (int) ceil(mb_strlen($user) / 4));
        $completionTokens = max(1, (int) ceil(mb_strlen($text) / 4));

        return [
            'text' => $text,
            'prompt_tokens' => $promptTokens,
            'completion_tokens' => $completionTokens,
            'model' => 'local-heuristic-v1',
            'raw' => $result,
        ];
    }
}
