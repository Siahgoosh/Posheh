<?php

namespace App\Modules\Communication\Application\Services;

use App\Models\Communication\CommAttachment;
use App\Models\Communication\CommChannelMessageMap;
use App\Models\Communication\CommConversation;
use App\Models\Communication\CommLead;
use App\Models\Communication\CommLeadNote;
use App\Models\Communication\CommTelegramAccount;
use App\Models\Communication\CommTelegramUpdate;
use App\Models\Communication\CommVisitor;
use App\Models\User;
use App\Modules\Communication\Domain\Enums\CommChannel;
use App\Modules\Communication\Domain\Enums\ConversationStatus;
use App\Modules\Communication\Infrastructure\Telegram\TelegramApiClient;
use Illuminate\Support\Str;

class TelegramPlatformHandler
{
    public function __construct(
        private readonly TelegramApiClient $api,
        private readonly MessageService $messages,
        private readonly TicketService $tickets,
    ) {}

    public function handle(array $update): void
    {
        if (! $this->api->isConfigured()) {
            return;
        }

        $updateId = $update['update_id'] ?? null;
        if ($updateId && CommTelegramUpdate::where('update_id', $updateId)->exists()) {
            return;
        }

        if ($updateId) {
            CommTelegramUpdate::create(['update_id' => $updateId, 'payload' => $update]);
        }

        if (isset($update['callback_query'])) {
            $this->handleCallback($update['callback_query']);

            return;
        }

        $message = $update['message'] ?? null;
        if (! $message) {
            return;
        }

        $chatId = $message['chat']['id'] ?? null;
        $from = $message['from'] ?? [];
        $telegramUserId = $from['id'] ?? null;
        if (! $chatId || ! $telegramUserId) {
            return;
        }

        $operatorAccount = CommTelegramAccount::where('telegram_user_id', $telegramUserId)
            ->where('account_type', 'operator')
            ->where('is_active', true)
            ->first();

        if ($operatorAccount) {
            $this->handleOperatorMessage($operatorAccount, $message);

            return;
        }

        $this->handleVisitorMessage($chatId, $from, $message);
    }

    private function handleVisitorMessage(int|string $chatId, array $from, array $message): void
    {
        $text = trim((string) ($message['text'] ?? ''));
        if (str_starts_with($text, '/start')) {
            $this->api->sendMessage($chatId, "سلام! به پشتیبانی پوشه خوش آمدید.\nپیام خود را بنویسید؛ تیم ما پاسخ می‌دهد.");

            return;
        }

        $account = CommTelegramAccount::firstOrCreate(
            ['telegram_user_id' => $from['id']],
            [
                'account_type' => 'visitor',
                'telegram_chat_id' => $chatId,
                'username' => $from['username'] ?? null,
                'first_name' => $from['first_name'] ?? null,
            ],
        );

        $visitor = $account->visitor;
        if (! $visitor) {
            $visitor = CommVisitor::create([
                'uuid' => (string) Str::uuid(),
                'first_name' => $from['first_name'] ?? 'مهمان',
                'visit_count' => 1,
                'first_visit_at' => now(),
                'last_visit_at' => now(),
            ]);
            $account->update(['visitor_id' => $visitor->id]);
        }

        $conversation = CommConversation::query()
            ->where('visitor_id', $visitor->id)
            ->where('channel', CommChannel::Telegram->value)
            ->where('status', '!=', ConversationStatus::Closed->value)
            ->orderByDesc('id')
            ->first();

        if (! $conversation) {
            $conversation = CommConversation::create([
                'uuid' => (string) Str::uuid(),
                'visitor_id' => $visitor->id,
                'channel' => CommChannel::Telegram->value,
                'status' => ConversationStatus::Open->value,
                'external_chat_id' => (string) $chatId,
                'subject' => 'تلگرام — '.($from['first_name'] ?? 'مهمان'),
                'last_message_at' => now(),
            ]);
        }

        if ($text) {
            $commMessage = $this->messages->sendFromVisitor($conversation, $text);
            $this->mapTelegramMessage($message, $conversation->id, $commMessage->id);
        } else {
            $this->handleInboundMedia($conversation, $message, $chatId);
        }
    }

    private function handleOperatorMessage(CommTelegramAccount $account, array $message): void
    {
        $text = trim((string) ($message['text'] ?? ''));
        $chatId = $message['chat']['id'];

        if (str_starts_with($text, '/link ')) {
            $mobile = trim(substr($text, 6));
            $user = User::where('mobile', $mobile)->whereIn('role', ['super_admin', 'platform_admin', 'platform_support', 'platform_finance'])->first();
            if ($user) {
                $account->update(['user_id' => $user->id]);
                $this->api->sendMessage($chatId, "✅ حساب اپراتور متصل شد: {$user->name}");
            } else {
                $this->api->sendMessage($chatId, 'کاربر پلتفرم با این موبایل پیدا نشد.');
            }

            return;
        }

        if (str_starts_with($text, '/help')) {
            $this->api->sendMessage($chatId, $this->operatorHelpText());

            return;
        }

        $conversation = $this->conversationFromContext($message);
        if (! $conversation && preg_match('/^\/(\w+)\s+([a-f0-9-]{8,})/i', $text, $m)) {
            $conversation = $this->findConversationByShortUuid($m[2]);
            $cmd = $m[1];
            $arg = trim(substr($text, strlen($m[0])));
            $this->runOperatorCommand($account, $conversation, $cmd, $arg, $chatId);

            return;
        }

        if (! $conversation) {
            $this->api->sendMessage($chatId, 'گفتگو مشخص نیست. روی اعلان پاسخ دهید یا /help');

            return;
        }

        if ($text) {
            $userId = $account->user_id;
            if (! $userId) {
                $this->api->sendMessage($chatId, 'ابتدا با /link موبایل خود را متصل کنید.');

                return;
            }
            $commMessage = $this->messages->sendFromOperator($conversation, $userId, $text, false);
            $this->mapTelegramMessage($message, $conversation->id, $commMessage->id);
            $this->api->sendMessage($chatId, '✅ پیام ارسال شد به مشتری.');
        } else {
            $this->handleOperatorMedia($account, $conversation, $message, $chatId);
        }
    }

    private function runOperatorCommand(CommTelegramAccount $account, ?CommConversation $conversation, string $cmd, string $arg, int|string $chatId): void
    {
        if (! $conversation) {
            $this->api->sendMessage($chatId, 'گفتگو پیدا نشد.');

            return;
        }

        $userId = $account->user_id;
        if (! $userId) {
            $this->api->sendMessage($chatId, 'ابتدا /link موبایل');

            return;
        }

        match ($cmd) {
            'close' => $conversation->update(['status' => ConversationStatus::Closed->value]),
            'status' => $conversation->update(['status' => $arg ?: ConversationStatus::Open->value]),
            'assign' => $conversation->update(['assigned_to' => (int) $arg]),
            'note' => CommLeadNote::create([
                'lead_id' => $conversation->lead_id,
                'user_id' => $userId,
                'body' => $arg,
            ]),
            'ticket' => $this->tickets->createFromConversation($conversation, $userId),
            default => null,
        };

        $this->api->sendMessage($chatId, "✅ دستور /{$cmd} انجام شد.");
    }

    private function handleCallback(array $callback): void
    {
        $data = (string) ($callback['data'] ?? '');
        $chatId = $callback['message']['chat']['id'] ?? null;
        if (! $chatId || ! str_contains($data, 'conv:')) {
            return;
        }

        $uuidPart = Str::after($data, 'conv:');
        $conversation = $this->findConversationByShortUuid($uuidPart);
        if ($conversation) {
            $this->api->sendMessage($chatId, "گفتگو: {$conversation->subject}\nپاسخ خود را بنویسید یا فایل ارسال کنید.");
            CommChannelMessageMap::create([
                'channel' => CommChannel::Telegram->value,
                'external_message_id' => 'ctx:'.$chatId,
                'conversation_id' => $conversation->id,
                'map_type' => 'operator_context',
            ]);
        }
    }

    private function conversationFromContext(array $message): ?CommConversation
    {
        $chatId = $message['chat']['id'];
        $ctx = CommChannelMessageMap::where('channel', CommChannel::Telegram->value)
            ->where('external_message_id', 'ctx:'.$chatId)
            ->orderByDesc('id')
            ->first();
        if ($ctx) {
            return CommConversation::find($ctx->conversation_id);
        }

        $replyTo = $message['reply_to_message']['message_id'] ?? null;
        if ($replyTo) {
            $map = CommChannelMessageMap::where('channel', CommChannel::Telegram->value)
                ->where('external_message_id', (string) $replyTo)
                ->first();
            if ($map) {
                return CommConversation::find($map->conversation_id);
            }
        }

        return null;
    }

    private function findConversationByShortUuid(string $part): ?CommConversation
    {
        return CommConversation::where('uuid', 'like', $part.'%')->first();
    }

    private function handleInboundMedia(CommConversation $conversation, array $message, int|string $chatId): void
    {
        $type = 'file';
        $fileId = null;
        if (isset($message['photo'])) {
            $type = 'image';
            $fileId = end($message['photo'])['file_id'] ?? null;
        } elseif (isset($message['document'])) {
            $fileId = $message['document']['file_id'] ?? null;
        } elseif (isset($message['voice'])) {
            $type = 'voice';
            $fileId = $message['voice']['file_id'] ?? null;
        } elseif (isset($message['video'])) {
            $type = 'video';
            $fileId = $message['video']['file_id'] ?? null;
        }

        if (! $fileId) {
            return;
        }

        $local = $this->downloadTelegramFile($fileId);
        if (! $local) {
            return;
        }

        $body = $message['caption'] ?? '['.$type.']';
        $commMessage = $this->messages->sendFromVisitor($conversation, $body);
        CommAttachment::create([
            'message_id' => $commMessage->id,
            'conversation_id' => $conversation->id,
            'path' => $local,
            'original_name' => basename($local),
            'message_type' => $type,
        ]);
    }

    private function handleOperatorMedia(CommTelegramAccount $account, CommConversation $conversation, array $message, int|string $chatId): void
    {
        $userId = $account->user_id;
        if (! $userId) {
            return;
        }

        $type = 'file';
        $fileId = null;
        if (isset($message['photo'])) {
            $type = 'image';
            $fileId = end($message['photo'])['file_id'] ?? null;
        } elseif (isset($message['document'])) {
            $fileId = $message['document']['file_id'] ?? null;
        } elseif (isset($message['voice'])) {
            $type = 'voice';
            $fileId = $message['voice']['file_id'] ?? null;
        } elseif (isset($message['video'])) {
            $type = 'video';
            $fileId = $message['video']['file_id'] ?? null;
        }

        if (! $fileId) {
            return;
        }

        $local = $this->downloadTelegramFile($fileId);
        if (! $local) {
            return;
        }

        $body = $message['caption'] ?? '['.$type.']';
        $commMessage = $this->messages->sendFromOperator($conversation, $userId, $body, false);
        CommAttachment::create([
            'message_id' => $commMessage->id,
            'conversation_id' => $conversation->id,
            'path' => $local,
            'message_type' => $type,
        ]);
        $this->api->sendMessage($chatId, '✅ فایل ارسال شد.');
    }

    private function downloadTelegramFile(string $fileId): ?string
    {
        $info = $this->api->getFile($fileId);
        $path = $info['result']['file_path'] ?? null;
        if (! $path) {
            return null;
        }

        return $this->api->downloadFile($path);
    }

    private function mapTelegramMessage(array $message, int $conversationId, int $messageId): void
    {
        if (! isset($message['message_id'])) {
            return;
        }

        CommChannelMessageMap::create([
            'channel' => CommChannel::Telegram->value,
            'external_message_id' => (string) $message['message_id'],
            'conversation_id' => $conversationId,
            'message_id' => $messageId,
            'map_type' => 'message',
        ]);
    }

    private function operatorHelpText(): string
    {
        return "دستورات اپراتور:\n/link 0912...\n/close {uuid}\n/ticket {uuid}\n/note {uuid} متن\n/status {uuid} open|closed\n/assign {uuid} user_id\n\nیا Reply روی اعلان پیام مشتری.";
    }
}
