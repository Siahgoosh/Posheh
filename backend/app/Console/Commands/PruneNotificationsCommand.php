<?php

namespace App\Console\Commands;

use App\Models\BroadcastMessage;
use App\Models\BroadcastMessageRead;
use App\Services\Settings\SystemSettingsService;
use Illuminate\Console\Command;

class PruneNotificationsCommand extends Command
{
    protected $signature = 'notifications:prune';

    protected $description = 'Remove broadcast notifications older than configured TTL (default 72h)';

    public function handle(SystemSettingsService $settings): int
    {
        $hours = max(1, (int) ($settings->get('notification_ttl_hours', '72') ?: 72));
        $cutoff = now()->subHours($hours);

        $messageIds = BroadcastMessage::query()
            ->where('sent_at', '<', $cutoff)
            ->pluck('id');

        $readsDeleted = BroadcastMessageRead::query()
            ->whereIn('broadcast_message_id', $messageIds)
            ->delete();

        $messagesDeleted = BroadcastMessage::query()
            ->whereIn('id', $messageIds)
            ->delete();

        $this->info("Pruned {$messagesDeleted} messages and {$readsDeleted} read records older than {$hours}h.");

        return self::SUCCESS;
    }
}
