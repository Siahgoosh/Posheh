<?php

namespace App\Modules\Communication\Infrastructure\Email;

use App\Models\Communication\CommConversation;
use App\Models\Communication\CommEmailMessage;
use App\Models\Communication\CommEmailThread;
use App\Models\Communication\CommMessage;
use App\Models\Communication\CommMessageStatus;
use App\Models\Communication\CommWebhookLog;
use App\Modules\Communication\Application\Contracts\ChannelGatewayInterface;
use App\Modules\Communication\Domain\Enums\CommChannel;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EmailChannelGateway implements ChannelGatewayInterface
{
    public function channel(): string
    {
        return CommChannel::Email->value;
    }

    public function isConfigured(): bool
    {
        return (bool) config('communication.email.from_address');
    }

    public function sendText(CommConversation $conversation, CommMessage $message, array $options = []): bool
    {
        if (! $this->isConfigured()) {
            return false;
        }

        $thread = CommEmailThread::where('conversation_id', $conversation->id)->first();
        if (! $thread) {
            return false;
        }

        $to = $conversation->lead?->email ?? $conversation->visitor?->email;
        if (! $to) {
            return false;
        }

        try {
            Mail::raw($message->body, function ($mail) use ($to, $thread, $message) {
                $mail->to($to)
                    ->from(config('communication.email.from_address'), config('communication.email.from_name'))
                    ->subject($thread->subject ?? 'پاسخ پشتیبانی پوشه')
                    ->replyTo($thread->alias_email);
            });

            CommEmailMessage::create([
                'thread_id' => $thread->id,
                'message_id' => $message->id,
                'direction' => 'outbound',
                'from_email' => config('communication.email.from_address'),
                'to_email' => $to,
                'subject' => $thread->subject,
                'body_text' => $message->body,
            ]);

            CommMessageStatus::updateOrCreate(
                ['message_id' => $message->id, 'channel' => $this->channel()],
                ['status' => 'sent'],
            );

            return true;
        } catch (\Throwable $e) {
            Log::warning('Email send failed', ['error' => $e->getMessage()]);
            CommWebhookLog::create([
                'provider' => 'email',
                'event' => 'send_error',
                'payload' => ['error' => $e->getMessage()],
                'ok' => false,
            ]);

            return false;
        }
    }

    public function sendMedia(CommConversation $conversation, CommMessage $message, string $type, string $path, array $options = []): bool
    {
        // Attachment email — logged for future MIME implementation
        CommWebhookLog::create([
            'provider' => 'email',
            'event' => 'send_media_stub',
            'payload' => ['type' => $type, 'path' => basename($path)],
            'ok' => false,
        ]);

        return false;
    }
}
