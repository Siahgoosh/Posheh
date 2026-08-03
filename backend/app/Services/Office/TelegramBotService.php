<?php

namespace App\Services\Office;

use App\Models\Office;
use App\Models\OfficeVisitRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramBotService
{
    public static function buildWebhookUrl(Office $office): string
    {
        $base = config('services.telegram.webhook_base_url')
            ?? config('app.webhook_url')
            ?? config('app.url');

        $base = rtrim((string) $base, '/');

        if (config('services.telegram.force_https', true) && str_starts_with($base, 'http://')) {
            $base = 'https://'.substr($base, 7);
        }

        return $base.'/api/v1/bots/telegram/'.$office->slug;
    }

    /** @return array{ok: bool, message: string, bot_username?: string, webhook_url?: string}> */
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

        $webhookUrl = self::buildWebhookUrl($office);

        if (! str_starts_with($webhookUrl, 'https://')) {
            return [
                'ok' => false,
                'message' => 'آدرس webhook باید HTTPS باشد. APP_URL یا TELEGRAM_WEBHOOK_BASE_URL را روی https تنظیم کنید.',
                'webhook_url' => $webhookUrl,
            ];
        }

        try {
            $response = Http::timeout(20)->post("https://api.telegram.org/bot{$token}/setWebhook", [
                'url' => $webhookUrl,
                'allowed_updates' => ['message', 'callback_query'],
                'drop_pending_updates' => true,
            ]);

            $body = $response->json();
            if (! ($body['ok'] ?? false)) {
                Log::warning('Telegram setWebhook failed', ['office' => $office->slug, 'body' => $body, 'url' => $webhookUrl]);

                return [
                    'ok' => false,
                    'message' => $body['description'] ?? 'خطا در ثبت webhook',
                    'webhook_url' => $webhookUrl,
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

    public function notifyVisitRequest(
        Office $office,
        OfficeVisitRequest $request,
        ?string $propertyCode,
        string $schedule,
    ): void {
        $token = trim((string) $office->telegram_bot_token);
        $adminChatId = trim((string) $office->telegram_admin_chat_id);

        if ($token === '' || $adminChatId === '') {
            return;
        }

        $propertyLine = $propertyCode
            ? "🏠 ملک: <b>{$propertyCode}</b>"
            : '🏠 درخواست عمومی (بدون ملک مشخص)';

        $text = "📅 <b>درخواست بازدید جدید</b>\n";
        $text .= "دفتر: {$office->name}\n\n";
        $text .= "👤 {$request->name}\n";
        $text .= "📱 <code>{$request->mobile}</code>\n";
        $text .= "{$propertyLine}\n";
        $text .= "🗓 {$schedule}\n";

        if ($request->email) {
            $text .= "✉️ {$request->email}\n";
        }
        if ($request->message) {
            $text .= "\n💬 {$request->message}";
        }

        $panelUrl = rtrim((string) config('app.url'), '/').'/visits';
        $text .= "\n\n🔗 <a href=\"{$panelUrl}\">مشاهده در پنل</a>";

        try {
            Http::timeout(15)->post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id' => $adminChatId,
                'text' => $text,
                'parse_mode' => 'HTML',
                'disable_web_page_preview' => true,
            ]);
        } catch (\Throwable $e) {
            Log::error('Telegram visit request notify failed', [
                'office' => $office->slug,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
