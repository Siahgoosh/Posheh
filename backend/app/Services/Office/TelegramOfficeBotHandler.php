<?php

namespace App\Services\Office;

use App\Enums\PropertyStatus;
use App\Enums\UserRole;
use App\Models\Office;
use App\Models\Property;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramOfficeBotHandler
{
    /** Handle one Telegram update — strictly scoped to the resolved office. */
    public function handle(Office $office, array $update): void
    {
        $token = trim((string) $office->telegram_bot_token);
        if ($token === '') {
            return;
        }

        if (isset($update['callback_query'])) {
            $this->handleCallback($office, $token, $update['callback_query']);

            return;
        }

        $message = $update['message'] ?? null;
        if (! $message) {
            return;
        }

        $chatId = $message['chat']['id'] ?? null;
        $text = trim((string) ($message['text'] ?? ''));

        if (! $chatId) {
            return;
        }

        if (str_starts_with($text, '/start')) {
            $this->notifyAdmin($office, $token, $chatId, $message);
            $this->sendWelcome($office, $token, $chatId);

            return;
        }

        if (str_starts_with($text, '/help') || $text === '❓ راهنما') {
            $this->sendHelp($office, $token, $chatId);

            return;
        }

        if (str_starts_with($text, '/list') || str_starts_with($text, '/properties') || $text === '🏢 املاک') {
            $this->sendProperties($office, $token, $chatId);

            return;
        }

        if (str_starts_with($text, '/agents') || $text === '👥 مشاوران') {
            $this->sendAgents($office, $token, $chatId);

            return;
        }

        if (str_starts_with($text, '/contact') || $text === '📞 تماس') {
            $this->sendContact($office, $token, $chatId);

            return;
        }

        if (str_starts_with($text, '/about') || $text === 'ℹ️ درباره دفتر') {
            $this->sendAbout($office, $token, $chatId);

            return;
        }

        $this->sendMessage($token, $chatId, 'از منوی زیر استفاده کنید یا /start را بزنید.', $this->mainKeyboard($office));
    }

    private function handleCallback(Office $office, string $token, array $callback): void
    {
        $chatId = $callback['message']['chat']['id'] ?? null;
        $callbackId = $callback['id'] ?? null;
        $data = (string) ($callback['data'] ?? '');

        if ($callbackId) {
            $this->answerCallback($token, $callbackId);
        }

        if (! $chatId || $data === '') {
            return;
        }

        match ($data) {
            'menu' => $this->sendWelcome($office, $token, $chatId),
            'props' => $this->sendProperties($office, $token, $chatId),
            'agents' => $this->sendAgents($office, $token, $chatId),
            'contact' => $this->sendContact($office, $token, $chatId),
            'about' => $this->sendAbout($office, $token, $chatId),
            'help' => $this->sendHelp($office, $token, $chatId),
            default => str_starts_with($data, 'prop:')
                ? $this->sendPropertyDetail($office, $token, $chatId, (int) substr($data, 5))
                : $this->sendMessage($token, $chatId, 'گزینه نامعتبر.', $this->mainKeyboard($office)),
        };
    }

    private function sendWelcome(Office $office, string $token, int|string $chatId): void
    {
        $brand = ($office->settings ?? [])['brand_name'] ?? $office->name;
        $text = "سلام! به ربات {$brand} خوش آمدید.\n\n";
        $text .= "از دکمه‌های زیر برای مشاهده املاک، مشاوران و اطلاعات تماس استفاده کنید.";

        $this->sendMessage($token, $chatId, $text, $this->mainKeyboard($office));
    }

    private function sendHelp(Office $office, string $token, int|string $chatId): void
    {
        $text = "راهنمای ربات {$office->name}:\n\n";
        $text .= "🏢 املاک — آخرین فایل‌های فعال\n";
        $text .= "👥 مشاوران — لیست تیم\n";
        $text .= "📞 تماس — شماره و آدرس دفتر\n";
        $text .= "ℹ️ درباره — معرفی دفتر\n";
        if ($office->publicWebsiteUrl()) {
            $text .= "🌐 وبسایت — لینک مستقیم سایت اختصاصی\n";
        }

        $this->sendMessage($token, $chatId, $text, $this->mainKeyboard($office));
    }

    private function sendProperties(Office $office, string $token, int|string $chatId): void
    {
        $items = Property::where('office_id', $office->id)
            ->where('status', PropertyStatus::Active)
            ->latest()
            ->limit(8)
            ->get();

        if ($items->isEmpty()) {
            $this->sendMessage($token, $chatId, 'در حال حاضر ملک فعالی ثبت نشده است.', $this->mainKeyboard($office));

            return;
        }

        $lines = ["🏢 آخرین املاک {$office->name}:\n"];
        $buttons = [];

        foreach ($items as $p) {
            $lines[] = $this->formatPropertyLine($p);
            $buttons[] = [['text' => "📋 {$p->code}", 'callback_data' => "prop:{$p->id}"]];
        }

        $buttons[] = [['text' => '🔙 منوی اصلی', 'callback_data' => 'menu']];

        $this->sendMessage($token, $chatId, implode("\n", $lines), ['inline_keyboard' => $buttons]);
    }

    private function sendPropertyDetail(Office $office, string $token, int|string $chatId, int $propertyId): void
    {
        $property = Property::where('office_id', $office->id)
            ->where('id', $propertyId)
            ->where('status', PropertyStatus::Active)
            ->first();

        if (! $property) {
            $this->sendMessage($token, $chatId, 'ملک یافت نشد یا دیگر فعال نیست.', $this->mainKeyboard($office));

            return;
        }

        $text = "🏠 {$property->code}\n";
        $text .= 'نوع: '.$this->typeLabel($property)."\n";
        if ($property->price) {
            $text .= 'قیمت: '.number_format((int) $property->price)." تومان\n";
        }
        if ($property->rent) {
            $text .= 'اجاره: '.number_format((int) $property->rent)." تومان\n";
        }
        if ($property->deposit) {
            $text .= 'ودیعه: '.number_format((int) $property->deposit)." تومان\n";
        }
        if ($property->area) {
            $text .= "متراژ: {$property->area} متر\n";
        }
        if ($property->rooms) {
            $text .= "اتاق: {$property->rooms}\n";
        }
        $text .= '📍 '.trim(($property->city ?? '').' '.($property->district ?? ''));
        if ($property->description) {
            $desc = mb_substr(strip_tags($property->description), 0, 200);
            $text .= "\n\n{$desc}";
        }

        $keyboard = ['inline_keyboard' => [
            [['text' => '🔙 بازگشت به لیست', 'callback_data' => 'props']],
            [['text' => '🔙 منوی اصلی', 'callback_data' => 'menu']],
        ]];

        $siteUrl = $office->publicWebsiteUrl();
        if ($siteUrl) {
            array_unshift($keyboard['inline_keyboard'], [['text' => '🌐 مشاهده در وبسایت', 'url' => $siteUrl]]);
        }

        $this->sendMessage($token, $chatId, $text, $keyboard);
    }

    private function sendAgents(Office $office, string $token, int|string $chatId): void
    {
        $agents = User::where('office_id', $office->id)
            ->where('is_active', true)
            ->whereIn('role', [UserRole::OfficeManager, UserRole::Consultant])
            ->orderByRaw("CASE WHEN role = 'office_manager' THEN 0 ELSE 1 END")
            ->orderBy('name')
            ->get(['name', 'mobile', 'role']);

        if ($agents->isEmpty()) {
            $this->sendMessage($token, $chatId, 'مشاوری ثبت نشده است.', $this->mainKeyboard($office));

            return;
        }

        $lines = ["👥 تیم {$office->name}:\n"];
        foreach ($agents as $agent) {
            $role = $agent->role === UserRole::OfficeManager ? 'مدیر' : 'مشاور';
            $line = "• {$agent->name} ({$role})";
            if ($agent->mobile) {
                $line .= "\n  📱 {$agent->mobile}";
            }
            $lines[] = $line;
        }

        $this->sendMessage($token, $chatId, implode("\n\n", $lines), $this->mainKeyboard($office));
    }

    private function sendContact(Office $office, string $token, int|string $chatId): void
    {
        $wa = $office->whatsapp_config ?? [];
        $text = "📞 تماس با {$office->name}\n\n";

        if ($office->phone) {
            $text .= "تلفن: {$office->phone}\n";
        }
        if (! empty($wa['phone'])) {
            $text .= "واتساپ: {$wa['phone']}\n";
        }
        if ($office->address) {
            $text .= "آدرس: {$office->address}\n";
        }
        if ($office->city) {
            $text .= "شهر: {$office->city}\n";
        }

        $siteUrl = $office->publicWebsiteUrl();
        if ($siteUrl) {
            $text .= "\n🌐 {$siteUrl}";
        }

        $this->sendMessage($token, $chatId, trim($text), $this->mainKeyboard($office));
    }

    private function sendAbout(Office $office, string $token, int|string $chatId): void
    {
        $brand = ($office->settings ?? [])['brand_name'] ?? $office->name;
        $text = "ℹ️ درباره {$brand}\n\n";
        $text .= $office->description ?: $office->website_description ?: 'دفتر املاک حرفه‌ای با خدمات مشاوره، خرید، فروش و اجاره.';

        if ($office->is_verified) {
            $text .= "\n\n✅ دفتر تأییدشده در پلتفرم پوشه";
        }

        $this->sendMessage($token, $chatId, $text, $this->mainKeyboard($office));
    }

  /** @return array<string, mixed> */
    private function mainKeyboard(Office $office): array
    {
        $rows = [];

        $siteUrl = $office->publicWebsiteUrl();
        if ($siteUrl) {
            $rows[] = [['text' => '🌐 وبسایت ما', 'url' => $siteUrl]];
        }

        $rows[] = [
            ['text' => '🏢 املاک', 'callback_data' => 'props'],
            ['text' => '👥 مشاوران', 'callback_data' => 'agents'],
        ];
        $rows[] = [
            ['text' => '📞 تماس', 'callback_data' => 'contact'],
            ['text' => 'ℹ️ درباره', 'callback_data' => 'about'],
        ];
        $rows[] = [['text' => '❓ راهنما', 'callback_data' => 'help']];

        return ['inline_keyboard' => $rows];
    }

    private function formatPropertyLine(Property $property): string
    {
        $price = $property->price ? number_format((int) $property->price).' ت' : 'تماس';
        $loc = trim(($property->city ?? '').' · '.($property->district ?? ''));

        return "▫️ {$property->code} — {$this->typeLabel($property)} — {$price}\n   {$loc}";
    }

    private function typeLabel(Property $property): string
    {
        $type = $property->type;
        if ($type instanceof \BackedEnum) {
            return method_exists($type, 'label') ? $type->label() : $type->value;
        }

        return (string) $type;
    }

    private function notifyAdmin(Office $office, string $token, int|string $chatId, array $message): void
    {
        $adminChatId = trim((string) $office->telegram_admin_chat_id);
        if ($adminChatId === '') {
            return;
        }

        $name = trim(($message['from']['first_name'] ?? '').' '.($message['from']['last_name'] ?? ''));
        $username = $message['from']['username'] ?? null;
        $text = "🔔 کاربر جدید در ربات {$office->name}\n";
        $text .= "Chat ID: {$chatId}\n";
        if ($name) {
            $text .= "نام: {$name}\n";
        }
        if ($username) {
            $text .= "@{$username}";
        }

        $this->sendMessage($token, $adminChatId, $text);
    }

    /** @param array<string, mixed>|null $replyMarkup */
    private function sendMessage(string $token, int|string $chatId, string $text, ?array $replyMarkup = null): void
    {
        try {
            $payload = [
                'chat_id' => $chatId,
                'text' => $text,
                'parse_mode' => 'HTML',
            ];

            if ($replyMarkup) {
                $payload['reply_markup'] = json_encode($replyMarkup);
            }

            Http::timeout(15)->post("https://api.telegram.org/bot{$token}/sendMessage", $payload);
        } catch (\Throwable $e) {
            Log::error('Telegram send failed', ['error' => $e->getMessage(), 'chat_id' => $chatId]);
        }
    }

    private function answerCallback(string $token, string $callbackId): void
    {
        try {
            Http::timeout(10)->post("https://api.telegram.org/bot{$token}/answerCallbackQuery", [
                'callback_query_id' => $callbackId,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Telegram callback answer failed', ['error' => $e->getMessage()]);
        }
    }
}
