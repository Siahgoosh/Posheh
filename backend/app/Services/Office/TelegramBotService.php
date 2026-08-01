<?php

namespace App\Services\Office;

use App\Models\Office;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramBotService
{
    /** @return array{ok: bool, message: string, bot_username?: string}> */
    public function configureWebhook(Office $office): array
    {
        $token = trim((string) $office->telegram_bot_token);
        if ($token === '') {
            return ['ok' => false, 'message' => 'توکن ربات خالی است.'];
        }

        $me = $this->getMe($token);
        if (! $me['ok']) {
            return $me;
        }

        $webhookUrl = rtrim(config('app.url'), '/').'/api/v1/bots/telegram/'.$office->slug;

        try {
            $response = Http::timeout(20)->post("https://api.telegram.org/bot{$token}/setWebhook", [
                'url' => $webhookUrl,
                'allowed_updates' => ['message', 'callback_query'],
                'drop_pending_updates' => true,
            ]);

            $body = $response->json();
            if (! ($body['ok'] ?? false)) {
                Log::warning('Telegram setWebhook failed', ['office' => $office->slug, 'body' => $body]);

                return [
                    'ok' => false,
                    'message' => $body['description'] ?? 'خطا در ثبت webhook',
                ];
            }

            return [
                'ok' => true,
                'message' => 'ربات متصل شد و webhook ثبت گردید.',
                'bot_username' => $me['bot_username'] ?? null,
                'webhook_url' => $webhookUrl,
            ];
        } catch (\Throwable $e) {
            Log::error('Telegram webhook exception', ['error' => $e->getMessage()]);

            return ['ok' => false, 'message' => 'خطا در اتصال به تلگرام: '.$e->getMessage()];
        }
    }

    /** @return array{ok: bool, message: string, bot_username?: string}> */
    public function getMe(string $token): array
    {
        try {
            $response = Http::timeout(15)->get("https://api.telegram.org/bot{$token}/getMe");
            $body = $response->json();
            if (! ($body['ok'] ?? false)) {
                return [
                    'ok' => false,
                    'message' => $body['description'] ?? 'توکن ربات نامعتبر است.',
                ];
            }

            return [
                'ok' => true,
                'message' => 'توکن معتبر است.',
                'bot_username' => $body['result']['username'] ?? null,
            ];
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => 'خطا در بررسی توکن: '.$e->getMessage()];
        }
    }
}
