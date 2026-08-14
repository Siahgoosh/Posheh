<?php

namespace App\Services\Ai\Providers;

use App\Contracts\Ai\AiProviderInterface;

/**
 * Deterministic mock provider for tests and offline Content OS.
 * Never invents factual claims; returns structured placeholders.
 */
class MockAiProvider implements AiProviderInterface
{
    public function key(): string
    {
        return 'mock';
    }

    public function isAvailable(): bool
    {
        return true;
    }

    public function complete(array $request): array
    {
        $user = (string) ($request['user'] ?? '');
        $system = (string) ($request['system'] ?? '');
        // Strip injection attempts from untrusted user content boundary
        $safeUser = preg_replace('/ignore\s+(all\s+)?(previous|prior)\s+instructions/iu', '[filtered]', $user) ?? $user;

        $text = "پیشنهاد پیش‌نویس محلی (Mock Provider)\n\n"
            ."این خروجی فقط برای تست pipeline است و ادعای واقع‌گرایانه درباره قیمت/قانون/آمار ندارد.\n"
            ."ورودی (خلاصه): ".mb_substr(strip_tags($safeUser), 0, 280)."\n"
            ."سیستم: ".mb_substr(strip_tags($system), 0, 80);

        $promptTokens = max(1, (int) ceil(mb_strlen($system.$safeUser) / 4));
        $completionTokens = max(1, (int) ceil(mb_strlen($text) / 4));

        return [
            'text' => $text,
            'prompt_tokens' => $promptTokens,
            'completion_tokens' => $completionTokens,
            'model' => 'mock-local-v1',
        ];
    }
}
