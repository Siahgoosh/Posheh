<?php

namespace App\Services\Ai;

/**
 * Provider registry. External LLM providers stay disabled until credentials + permission exist.
 * Never fabricate API credentials.
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
                'supports' => ['customer_summary', 'message_generation', 'listing_assistant'],
            ],
            [
                'key' => 'openai',
                'name' => 'OpenAI',
                'is_active' => false,
                'supports' => ['customer_summary', 'message_generation', 'listing_assistant', 'call_summary'],
                'note' => 'Requires office AI permission + API key. Not enabled by default.',
            ],
        ];
    }

    public function canCallExternal(int $officeId): bool
    {
        // External calls require explicit config — never auto-send PII
        return false;
    }
}
