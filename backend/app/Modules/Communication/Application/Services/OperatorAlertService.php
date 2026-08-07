<?php

namespace App\Modules\Communication\Application\Services;

use App\Models\Communication\CommChannelMessageMap;
use App\Models\Communication\CommConversation;
use App\Models\Communication\CommTelegramAccount;
use App\Modules\Communication\Domain\Enums\CommChannel;
use App\Modules\Communication\Infrastructure\Telegram\TelegramApiClient;
use Illuminate\Support\Str;

class OperatorAlertService
{
    public function __construct(
        private readonly TelegramApiClient $telegram,
        private readonly CommunicationSettingsService $commSettings,
        private readonly AiCopilotService $ai,
    ) {}

    public function notifyVisitorMessage(CommConversation $conversation, string $preview): void
    {
        if (! $this->telegram->isConfigured()) {
            return;
        }

        $conversation->loadMissing(['lead', 'visitor']);
        $text = $this->buildAlertText($conversation, $preview);
        $this->dispatchTelegramAlerts($conversation, $text);

        $analysis = $this->ai->summarize($conversation);
        if ($analysis['alert'] ?? false) {
            $this->sendToAllOperators('⚠️ <b>هشدار:</b> مشتری ناراضی به نظر می‌رسد!');
        }
    }

    public function notifyNewConversation(CommConversation $conversation): void
    {
        if (! $this->telegram->isConfigured()) {
            return;
        }

        $conversation->loadMissing(['lead', 'visitor']);
        $lead = $conversation->lead;
        $text = "🆕 <b>گفتگوی جدید</b>\n";
        $text .= $lead?->office_name ? "دفتر: {$lead->office_name}\n" : '';
        $text .= $lead?->mobile ? "موبایل: {$lead->mobile}\n" : '';
        $text .= $lead?->first_name ? "نام: {$lead->first_name}\n" : '';
        $text .= 'کانال: '.$conversation->channel."\n";
        $text .= 'امتیاز: '.($lead?->lead_score ?? $conversation->visitor?->lead_score ?? 0);

        $this->dispatchTelegramAlerts($conversation, $text);
    }

    private function buildAlertText(CommConversation $conversation, string $preview): string
    {
        $lead = $conversation->lead;
        $short = Str::substr($conversation->uuid, 0, 8);
        $channelLabel = match ($conversation->channel) {
            'telegram' => 'تلگرام',
            'whatsapp' => 'واتساپ',
            'email' => 'ایمیل',
            default => 'وبسایت',
        };

        $text = "🔔 <b>پیام جدید</b> ({$channelLabel})\n";
        $text .= $lead?->office_name ? "دفتر: {$lead->office_name}\n" : '';
        $text .= $lead?->mobile ? "موبایل: {$lead->mobile}\n" : '';
        $text .= 'امتیاز: '.($lead?->lead_score ?? $conversation->visitor?->lead_score ?? 0)."\n";
        $text .= 'پیام: '.Str::limit($preview, 200)."\n\n";
        $text .= "پاسخ: روی این پیام Reply کنید\n";
        $text .= "/close {$short} | /ticket {$short} | /note {$short} یادداشت";

        return $text;
    }

    private function dispatchTelegramAlerts(CommConversation $conversation, string $text): void
    {
        $operators = CommTelegramAccount::where('account_type', 'operator')->where('is_active', true)->get();
        foreach ($operators as $op) {
            $result = $this->telegram->sendMessage($op->telegram_chat_id, $text);
            if (($result['ok'] ?? false) && isset($result['result']['message_id'])) {
                CommChannelMessageMap::create([
                    'channel' => CommChannel::Telegram->value,
                    'external_message_id' => (string) $result['result']['message_id'],
                    'conversation_id' => $conversation->id,
                    'map_type' => 'operator_alert',
                ]);
            }
        }

        foreach ($this->commSettings->telegramAlertChatIds() as $alertChat) {
            if ($alertChat) {
                $this->telegram->sendMessage($alertChat, $text);
            }
        }
    }

    private function sendToAllOperators(string $text): void
    {
        $operators = CommTelegramAccount::where('account_type', 'operator')->where('is_active', true)->get();
        foreach ($operators as $op) {
            $this->telegram->sendMessage($op->telegram_chat_id, $text);
        }
    }
}
