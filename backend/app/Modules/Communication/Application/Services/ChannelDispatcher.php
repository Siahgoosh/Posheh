<?php

namespace App\Modules\Communication\Application\Services;

use App\Models\Communication\CommAttachment;
use App\Models\Communication\CommConversation;
use App\Models\Communication\CommMessage;
use App\Modules\Communication\Application\Contracts\ChannelGatewayInterface;
use App\Modules\Communication\Infrastructure\Email\EmailChannelGateway;
use App\Modules\Communication\Infrastructure\Telegram\TelegramChannelGateway;
use App\Modules\Communication\Infrastructure\WhatsApp\WhatsAppChannelGateway;

class ChannelDispatcher
{
    /** @var array<string, ChannelGatewayInterface> */
    private array $gateways = [];

    public function __construct(
        TelegramChannelGateway $telegram,
        WhatsAppChannelGateway $whatsapp,
        EmailChannelGateway $email,
    ) {
        $this->gateways[$telegram->channel()] = $telegram;
        $this->gateways[$whatsapp->channel()] = $whatsapp;
        $this->gateways[$email->channel()] = $email;
    }

    public function dispatchToVisitor(CommConversation $conversation, CommMessage $message): void
    {
        if ($message->is_internal) {
            return;
        }

        if (in_array($conversation->channel, ['website', 'web_app'], true)) {
            return;
        }

        $gateway = $this->gateways[$conversation->channel] ?? null;
        if (! $gateway || ! $gateway->isConfigured()) {
            return;
        }

        $attachment = CommAttachment::where('message_id', $message->id)->first();
        if ($attachment && is_file($attachment->path)) {
            $gateway->sendMedia($conversation, $message, $attachment->message_type, $attachment->path);

            return;
        }

        $gateway->sendText($conversation, $message);
    }

    public function gateway(string $channel): ?ChannelGatewayInterface
    {
        return $this->gateways[$channel] ?? null;
    }
}
