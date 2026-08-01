<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Office;
use App\Services\Office\TelegramOfficeBotHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BotWebhookController extends Controller
{
    public function __construct(
        private readonly TelegramOfficeBotHandler $telegramHandler,
    ) {}

    /**
     * Each office has its own BotFather token and webhook URL (/bots/telegram/{slug}).
     * Data is always scoped to the resolved office — no cross-tenant leakage.
     */
    public function telegram(Request $request, string $officeSlug): JsonResponse
    {
        $office = Office::where('slug', $officeSlug)->where('is_active', true)->firstOrFail();

        if (! trim((string) $office->telegram_bot_token)) {
            return response()->json(['ok' => false], 404);
        }

        try {
            $this->telegramHandler->handle($office, $request->all());
        } catch (\Throwable $e) {
            Log::error('Telegram webhook handler failed', [
                'office' => $office->slug,
                'error' => $e->getMessage(),
            ]);
        }

        return response()->json(['ok' => true]);
    }

    public function whatsapp(Request $request, string $officeSlug): JsonResponse
    {
        $office = Office::where('slug', $officeSlug)->where('is_active', true)->firstOrFail();
        $config = $office->whatsapp_config ?? [];

        Log::info('WhatsApp webhook', ['office' => $office->slug, 'payload' => $request->all()]);

        return response()->json([
            'ok' => true,
            'message' => 'پیام دریافت شد.',
            'reply' => $config['auto_reply'] ?? "سلام! به {$office->name} خوش آمدید. به‌زودی پاسخ می‌دهیم.",
        ]);
    }
}
