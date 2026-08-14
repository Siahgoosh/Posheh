<?php

namespace App\Services\Ai;

/**
 * Provider registry. External LLM providers stay disabled until credentials + permission exist.
 * Never fabricate API credentials.
 * Content OS uses ContentAiProviderRegistry; this remains for CRM AiService compatibility.
 */
class AiProviderManager
{
    public function available(): array
    {
        return [
            [
                'key' => 'local',
                'name' => 'Rule-Based Local',
                'is_active' => true,
                'supports' => ['customer_summary', 'message_generation', 'listing_assistant', 'content_ops'],
            ],
            [
                'key' => 'mock',
                'name' => 'Mock Provider',
                'is_active' => true,
                'supports' => ['content_ops', 'tests'],
            ],
            [
                'key' => 'openai',
                'name' => 'OpenAI',
                'is_active' => (bool) config('content_ops.openai_enabled', false),
                'supports' => ['customer_summary', 'message_generation', 'listing_assistant', 'call_summary', 'content_ops'],
                'note' => 'Requires CONTENT_AI_OPENAI_ENABLED=true + API key. Not enabled by default.',
            ],
        ];
    }

    public function canCallExternal(int $officeId): bool
    {
        // External calls require explicit config — never auto-send PII
        return false;
    }
}
