<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Office;
use App\Models\Property;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BotWebhookController extends Controller
{
    public function telegram(Request $request, string $officeSlug): JsonResponse
    {
        $office = Office::where('slug', $officeSlug)->where('is_active', true)->firstOrFail();

        if (! $office->telegram_bot_token) {
            return response()->json(['ok' => false], 404);
        }

        $update = $request->all();
        $chatId = $update['message']['chat']['id'] ?? null;
        $text = trim($update['message']['text'] ?? '');

        if (! $chatId) {
            return response()->json(['ok' => true]);
        }

        $reply = match (true) {
            str_starts_with($text, '/start') => "به ربات {$office->name} خوش آمدید.\nدستورات:\n/list — آخرین املاک\n/help — راهنما",
            str_starts_with($text, '/list') => $this->propertyListMessage($office),
            str_starts_with($text, '/help') => 'دستور /list لیست املاک فعال را نشان می‌دهد.',
            default => 'دستور نامعتبر. /help را بزنید.',
        };

        $this->sendTelegram($office->telegram_bot_token, $chatId, $reply);

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

    private function propertyListMessage(Office $office): string
    {
        $items = Property::where('office_id', $office->id)
            ->where('status', 'active')
            ->latest()
            ->limit(5)
            ->get(['code', 'type', 'price', 'city', 'district']);

        if ($items->isEmpty()) {
            return 'ملک فعالی ثبت نشده.';
        }

        return $items->map(fn ($p) => "{$p->code} — {$p->type} — ".number_format((int) $p->price).' — '.$p->city)->implode("\n");
    }

    private function sendTelegram(string $token, int|string $chatId, string $text): void
    {
        try {
            Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id' => $chatId,
                'text' => $text,
            ]);
        } catch (\Throwable $e) {
            Log::error('Telegram send failed', ['error' => $e->getMessage()]);
        }
    }
}
