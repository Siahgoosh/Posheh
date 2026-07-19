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
            str_starts_with($text, '/start') => "به ربات {$office->name} خوش آمدید.\n\nدستورات:\n/list — آخرین املاک\n/search — راهنمای جستجو\n/contact — تماس با دفتر\n/help — راهنما",
            str_starts_with($text, '/list') => $this->propertyListMessage($office),
            str_starts_with($text, '/search') => 'برای جستجو کد ملک یا نام محله را ارسال کنید.',
            str_starts_with($text, '/contact') => $this->contactMessage($office),
            str_starts_with($text, '/help') => "دستورات:\n/list — لیست املاک\n/search — جستجو\n/contact — اطلاعات تماس",
            default => $this->searchOrHelp($office, $text),
        };

        $this->sendTelegram($office->telegram_bot_token, $chatId, $reply);

        return response()->json(['ok' => true]);
    }

    public function whatsapp(Request $request, string $officeSlug): JsonResponse
    {
        $office = Office::where('slug', $officeSlug)->where('is_active', true)->firstOrFail();
        $config = $office->whatsapp_config ?? [];

        $from = $request->input('from') ?? $request->input('sender') ?? $request->input('mobile');
        $text = trim((string) ($request->input('text') ?? $request->input('message') ?? $request->input('body') ?? ''));

        Log::info('WhatsApp webhook', ['office' => $office->slug, 'from' => $from, 'text' => $text]);

        $reply = $config['auto_reply'] ?? "سلام! به {$office->name} خوش آمدید.";

        if (in_array(mb_strtolower($text), ['املاک', 'لیست', 'list', '/list'], true)) {
            $reply = $this->propertyListMessage($office);
        } elseif (mb_strlen($text) >= 2) {
            $matches = Property::where('office_id', $office->id)
                ->where('status', 'active')
                ->where(function ($q) use ($text) {
                    $q->where('code', 'like', "%{$text}%")
                        ->orWhere('district', 'like', "%{$text}%")
                        ->orWhere('city', 'like', "%{$text}%");
                })
                ->limit(5)
                ->get(['code', 'type', 'price', 'city', 'district']);

            if ($matches->isNotEmpty()) {
                $reply = $matches->map(fn ($p) => "{$p->code} — {$p->type} — ".number_format((int) $p->price).' — '.$p->city)->implode("\n");
            }
        }

        if (! empty($config['webhook_forward_url'])) {
            try {
                Http::timeout(10)->post($config['webhook_forward_url'], $request->all());
            } catch (\Throwable $e) {
                Log::warning('WhatsApp forward failed', ['error' => $e->getMessage()]);
            }
        }

        return response()->json([
            'ok' => true,
            'message' => 'پیام دریافت شد.',
            'reply' => $reply,
            'to' => $from,
        ]);
    }

    private function searchOrHelp(Office $office, string $text): string
    {
        if (mb_strlen($text) < 2) {
            return 'دستور نامعتبر. /help را بزنید.';
        }

        $matches = Property::where('office_id', $office->id)
            ->where('status', 'active')
            ->where(function ($q) use ($text) {
                $q->where('code', 'like', "%{$text}%")
                    ->orWhere('district', 'like', "%{$text}%")
                    ->orWhere('city', 'like', "%{$text}%")
                    ->orWhere('address', 'like', "%{$text}%");
            })
            ->limit(5)
            ->get(['code', 'type', 'price', 'city', 'district']);

        if ($matches->isEmpty()) {
            return "ملکی با «{$text}» یافت نشد. /list را امتحان کنید.";
        }

        return $matches->map(fn ($p) => "{$p->code} — {$p->type} — ".number_format((int) $p->price).' — '.$p->city)->implode("\n");
    }

    private function contactMessage(Office $office): string
    {
        $parts = array_filter([
            $office->name,
            $office->phone ? "تلفن: {$office->phone}" : null,
            $office->address ? "آدرس: {$office->address}" : null,
            $office->city,
        ]);

        return implode("\n", $parts) ?: 'اطلاعات تماس ثبت نشده.';
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
