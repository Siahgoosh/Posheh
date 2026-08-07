<?php

namespace App\Modules\Communication\Application\Contracts;

use App\Models\Communication\CommConversation;
use App\Models\Communication\CommMessage;

interface ChannelGatewayInterface
{
    public function channel(): string;

    public function isConfigured(): bool;

    /** @param array<string, mixed> $options */
    public function sendText(CommConversation $conversation, CommMessage $message, array $options = []): bool;

    /** @param array<string, mixed> $options */
    public function sendMedia(CommConversation $conversation, CommMessage $message, string $type, string $path, array $options = []): bool;
}
