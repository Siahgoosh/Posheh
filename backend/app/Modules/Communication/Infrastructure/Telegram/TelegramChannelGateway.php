<?php

namespace App\Modules\Communication\Infrastructure\Telegram;

use App\Models\Communication\CommAttachment;
use App\Models\Communication\CommChannelMessageMap;
use App\Models\Communication\CommConversation;
use App\Models\Communication\CommMessage;
use App\Models\Communication\CommMessageStatus;
use App\Modules\Communication\Application\Contracts\ChannelGatewayInterface;
use App\Modules\Communication\Domain\Enums\CommChannel;

class TelegramChannelGateway implements ChannelGatewayInterface
{
    public function __construct(private readonly TelegramApiClient $api) {}

    public function channel(): string
    {
        return CommChannel::Telegram->value;
    }

    public function isConfigured(): bool
    {
        return $this->api->isConfigured();
    }

    public function sendText(CommConversation $conversation, CommMessage $message, array $options = []): bool
    {
        $chatId = $conversation->external_chat_id;
        if (! $chatId || ! $this->isConfigured()) {
            return false;
        }

        $result = $this->api->sendMessage($chatId, $message->body, $options);
        $this->recordStatus($message, $result);

        if (($result['ok'] ?? false) && isset($result['result']['message_id'])) {
            $this->mapExternal((string) $result['result']['message_id'], $conversation->id, $message->id);
        }

        return (bool) ($result['ok'] ?? false);
    }

    public function sendMedia(CommConversation $conversation, CommMessage $message, string $type, string $path, array $options = []): bool
    {
        $chatId = $conversation->external_chat_id;
        if (! $chatId || ! $this->isConfigured() || ! is_file($path)) {
            return false;
        }

        $caption = $options['caption'] ?? null;
        $result = match ($type) {
            'image', 'photo' => $this->api->sendPhoto($chatId, $path, $caption),
            'voice', 'audio' => $this->api->sendVoice($chatId, $path),
            'video' => $this->api->sendVideo($chatId, $path, $caption),
            default => $this->api->sendDocument($chatId, $path, $caption),
        };

        $this->recordStatus($message, $result);

        if (($result['ok'] ?? false) && isset($result['result']['message_id'])) {
            $this->mapExternal((string) $result['result']['message_id'], $conversation->id, $message->id);
        }

        return (bool) ($result['ok'] ?? false);
    }

    /** @param array<string, mixed> $result */
    private function recordStatus(CommMessage $message, array $result): void
    {
        CommMessageStatus::updateOrCreate(
            ['message_id' => $message->id, 'channel' => $this->channel()],
            [
                'status' => ($result['ok'] ?? false) ? 'delivered' : 'failed',
                'external_id' => isset($result['result']['message_id']) ? (string) $result['result']['message_id'] : null,
                'meta' => $result,
            ],
        );
    }

    private function mapExternal(string $externalId, int $conversationId, int $messageId): void
    {
        CommChannelMessageMap::updateOrCreate(
            ['channel' => $this->channel(), 'external_message_id' => $externalId],
            ['conversation_id' => $conversationId, 'message_id' => $messageId, 'map_type' => 'message'],
        );
    }
}
