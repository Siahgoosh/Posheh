<?php

namespace App\Modules\Communication\Infrastructure\WhatsApp;

use App\Models\Communication\CommConversation;
use App\Models\Communication\CommMessage;
use App\Models\Communication\CommWebhookLog;
use App\Modules\Communication\Application\Contracts\ChannelGatewayInterface;
use App\Modules\Communication\Domain\Enums\CommChannel;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/** WhatsApp Cloud API — ready for credentials without structural changes. */
class WhatsAppChannelGateway implements ChannelGatewayInterface
{
    public function channel(): string
    {
        return CommChannel::WhatsApp->value;
    }

    public function isConfigured(): bool
    {
        return (bool) config('communication.whatsapp.phone_number_id')
            && (bool) config('communication.whatsapp.access_token');
    }

    public function sendText(CommConversation $conversation, CommMessage $message, array $options = []): bool
    {
        if (! $this->isConfigured() || ! $conversation->external_chat_id) {
            return false;
        }

        $token = config('communication.whatsapp.access_token');
        $phoneId = config('communication.whatsapp.phone_number_id');

        try {
            $response = Http::withToken($token)->post(
                "https://graph.facebook.com/v19.0/{$phoneId}/messages",
                [
                    'messaging_product' => 'whatsapp',
                    'to' => $conversation->external_chat_id,
                    'type' => 'text',
                    'text' => ['body' => $message->body],
                ],
            );

            CommWebhookLog::create([
                'provider' => 'whatsapp',
                'event' => 'send_text',
                'payload' => ['conversation' => $conversation->uuid],
                'ok' => $response->successful(),
            ]);

            return $response->successful();
        } catch (\Throwable $e) {
            Log::warning('WhatsApp send failed', ['error' => $e->getMessage()]);

            return false;
        }
    }

    public function sendMedia(CommConversation $conversation, CommMessage $message, string $type, string $path, array $options = []): bool
    {
        // Cloud API media upload — Part 3 full implementation
        CommWebhookLog::create([
            'provider' => 'whatsapp',
            'event' => 'send_media_stub',
            'payload' => ['type' => $type, 'conversation' => $conversation->uuid],
            'ok' => false,
        ]);

        return false;
    }
}
