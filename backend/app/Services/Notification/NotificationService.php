<?php

namespace App\Services\Notification;

use App\Models\User;
use App\Notifications\GenericNotification;
use Illuminate\Notifications\DatabaseNotification;

class NotificationService
{
    public function list(User $user, int $perPage = 20)
    {
        return $user->notifications()->latest()->paginate($perPage);
    }

    public function unreadCount(User $user): int
    {
        return $user->unreadNotifications()->count();
    }

    public function markAsRead(User $user, string $id): void
    {
        $user->notifications()->where('id', $id)->update(['read_at' => now()]);
    }

    public function markAllAsRead(User $user): void
    {
        $user->unreadNotifications()->update(['read_at' => now()]);
    }

    public function send(User $user, string $title, string $body, string $type = 'info', ?array $data = null): void
    {
        $user->notify(new GenericNotification($title, $body, $type, $data));
    }

    public function format(DatabaseNotification $notification): array
    {
        $data = $notification->data;

        return [
            'id' => $notification->id,
            'title' => $data['title'] ?? '',
            'body' => $data['body'] ?? '',
            'type' => $data['type'] ?? 'info',
            'data' => $data['data'] ?? null,
            'read_at' => $notification->read_at?->toIso8601String(),
            'created_at' => $notification->created_at?->toIso8601String(),
        ];
    }
}
