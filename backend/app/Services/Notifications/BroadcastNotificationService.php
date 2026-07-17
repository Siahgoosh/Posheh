<?php

namespace App\Services\Notifications;

use App\Models\BroadcastMessage;
use App\Models\BroadcastMessageRead;
use App\Models\User;
use Illuminate\Support\Collection;

class BroadcastNotificationService
{
    /** @param array<string, mixed> $data */
    public function send(User $sender, array $data): BroadcastMessage
    {
        $message = BroadcastMessage::create([
            'created_by' => $sender->id,
            'title' => $data['title'],
            'body' => $data['body'],
            'link_url' => $data['link_url'] ?? null,
            'image_url' => $data['image_url'] ?? null,
            'action_label' => $data['action_label'] ?? 'مشاهده',
            'priority' => $data['priority'] ?? 'normal',
            'target_platforms' => $data['target_platforms'] ?? ['web', 'android', 'windows'],
            'target_roles' => $data['target_roles'] ?? ['all'],
            'style' => $data['style'] ?? ['tone' => 'info'],
            'is_active' => true,
            'starts_at' => now(),
            'sent_at' => now(),
        ]);

        $recipients = $this->resolveRecipients($message);

        foreach ($recipients as $user) {
            BroadcastMessageRead::firstOrCreate(
                ['broadcast_message_id' => $message->id, 'user_id' => $user->id],
                ['delivered_at' => now()],
            );
        }

        return $message->loadCount('reads');
    }

    public function forUser(User $user, ?string $platform = 'web'): Collection
    {
        $roles = $this->userRoles($user);
        $platform = $platform ?: 'web';

        return BroadcastMessage::query()
            ->active()
            ->whereNotNull('sent_at')
            ->latest('sent_at')
            ->limit(50)
            ->get()
            ->filter(function (BroadcastMessage $message) use ($roles, $platform) {
                $platforms = $message->target_platforms ?? ['web', 'android', 'windows'];
                if (! in_array('all', $platforms, true) && ! in_array($platform, $platforms, true)) {
                    return false;
                }

                $targetRoles = $message->target_roles ?? ['all'];
                if (in_array('all', $targetRoles, true)) {
                    return true;
                }

                return count(array_intersect($roles, $targetRoles)) > 0;
            })
            ->map(function (BroadcastMessage $message) use ($user) {
                $read = BroadcastMessageRead::firstOrCreate(
                    ['broadcast_message_id' => $message->id, 'user_id' => $user->id],
                    ['delivered_at' => now()],
                );

                return [
                    'id' => $message->id,
                    'title' => $message->title,
                    'body' => $message->body,
                    'link_url' => $message->link_url,
                    'image_url' => $message->image_url,
                    'action_label' => $message->action_label,
                    'priority' => $message->priority,
                    'style' => $message->style,
                    'sent_at' => $message->sent_at?->toIso8601String(),
                    'is_read' => $read?->read_at !== null,
                    'delivered_at' => $read?->delivered_at?->toIso8601String(),
                ];
            })
            ->values();
    }

    public function markRead(User $user, int $messageId): void
    {
        BroadcastMessageRead::updateOrCreate(
            ['broadcast_message_id' => $messageId, 'user_id' => $user->id],
            ['read_at' => now(), 'delivered_at' => now()],
        );
    }

    /** @return Collection<int, User> */
    private function resolveRecipients(BroadcastMessage $message): Collection
    {
        $query = User::query()->where('is_active', true);
        $targetRoles = $message->target_roles ?? ['all'];

        if (! in_array('all', $targetRoles, true)) {
            $query->whereIn('role', $targetRoles);
        }

        return $query->get();
    }

    /** @return list<string> */
    private function userRoles(User $user): array
    {
        $role = $user->role?->value ?? (string) $user->role;

        return array_values(array_unique([$role, 'all']));
    }
}
