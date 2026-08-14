<?php

namespace App\Modules\Communication\Application\Services;

use App\Models\Communication\CommEmailMessage;
use App\Models\Communication\CommEmailThread;
use App\Models\Communication\CommMessage;
use App\Modules\Communication\Domain\Enums\ConversationStatus;

class EmailInboundService
{
    public function __construct(private readonly MessageService $messages) {}

  /** @param array<string, mixed> $data */
    public function processInbound(array $data): ?CommMessage
    {
        $thread = CommEmailThread::where('alias_email', $data['to_email'])->first();
        if (! $thread) {
            return null;
        }

        $conversation = $thread->conversation;
        if (! $conversation) {
            return null;
        }

        $body = trim((string) ($data['body_text'] ?? ''));
        if ($body === '' && ! empty($data['body_html'])) {
            $body = strip_tags((string) $data['body_html']);
        }
        if ($body === '') {
            $body = '[ایمیل بدون متن]';
        }

        $commMessage = $this->messages->sendFromVisitor($conversation, $body);

        CommEmailMessage::create([
            'thread_id' => $thread->id,
            'message_id' => $commMessage->id,
            'direction' => 'inbound',
            'from_email' => $data['from_email'],
            'to_email' => $data['to_email'],
            'subject' => $data['subject'] ?? null,
            'body_text' => $data['body_text'] ?? null,
            'body_html' => $data['body_html'] ?? null,
            'external_id' => $data['external_id'] ?? null,
        ]);

        $conversation->update([
            'status' => ConversationStatus::Open->value,
            'channel' => 'email',
        ]);

        return $commMessage;
    }
}
