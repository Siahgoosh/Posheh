<?php

namespace App\Modules\Communication\Application\Services;

use App\Models\Communication\CommConversation;
use App\Models\Communication\CommMessage;
use App\Modules\Communication\Domain\Enums\ConversationStatus;
use App\Modules\Communication\Domain\Enums\MessageSenderType;

class MessageService
{
    public function __construct(
        private readonly LeadScoringService $scoring,
        private readonly ChannelDispatcher $channels,
    ) {}

    public function sendFromVisitor(CommConversation $conversation, string $body): CommMessage
    {
        $message = CommMessage::create([
            'conversation_id' => $conversation->id,
            'sender_type' => MessageSenderType::Visitor->value,
            'body' => $body,
            'message_type' => 'text',
            'delivered_at' => now(),
        ]);

        $conversation->update([
            'last_message_at' => now(),
            'unread_operator' => $conversation->unread_operator + 1,
            'status' => ConversationStatus::Open->value,
        ]);

        if ($conversation->lead) {
            $this->scoring->recalculateLead($conversation->lead);
        }

        $this->channels->dispatchToVisitor($conversation->fresh(), $message);

        return $message;
    }

    public function sendFromOperator(CommConversation $conversation, int $userId, string $body, bool $internal = false, bool $dispatch = true): CommMessage
    {
        $message = CommMessage::create([
            'conversation_id' => $conversation->id,
            'sender_type' => MessageSenderType::Operator->value,
            'sender_id' => $userId,
            'body' => $body,
            'message_type' => 'text',
            'is_internal' => $internal,
            'delivered_at' => now(),
            'read_by_operator_at' => now(),
        ]);

        $conversation->update([
            'last_message_at' => now(),
            'unread_visitor' => $internal ? $conversation->unread_visitor : $conversation->unread_visitor + 1,
        ]);

        if (! $internal && $dispatch) {
            $this->channels->dispatchToVisitor($conversation->fresh(), $message);
        }

        return $message;
    }

    public function markReadByOperator(CommConversation $conversation): void
    {
        CommMessage::where('conversation_id', $conversation->id)
            ->where('sender_type', MessageSenderType::Visitor->value)
            ->whereNull('read_by_operator_at')
            ->update(['read_by_operator_at' => now()]);

        $conversation->update(['unread_operator' => 0]);
    }

    public function markReadByVisitor(CommConversation $conversation): void
    {
        CommMessage::where('conversation_id', $conversation->id)
            ->where('sender_type', MessageSenderType::Operator->value)
            ->whereNull('read_by_visitor_at')
            ->update(['read_by_visitor_at' => now()]);

        $conversation->update(['unread_visitor' => 0]);
    }
}
