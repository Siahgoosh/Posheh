<?php

namespace App\Services\Notification;

use App\Models\Announcement;
use App\Models\User;
use Illuminate\Support\Collection;

class UserNotificationService
{
    /** @return array{items: array<int, array<string, mixed>>, unread_count: int} */
    public function inbox(User $user): array
    {
        $db = $user->notifications()->latest()->limit(50)->get()->map(fn ($n) => [
            'id' => $n->id,
            'type' => class_basename($n->type),
            'title' => $n->data['title'] ?? 'اعلان',
            'body' => $n->data['body'] ?? '',
            'link' => $n->data['link'] ?? null,
            'icon' => $n->data['icon'] ?? 'bell',
            'read_at' => $n->read_at?->toIso8601String(),
            'created_at' => $n->created_at?->toIso8601String(),
            'source' => 'system',
        ]);

        $announcements = $this->activeAnnouncements()->map(fn (Announcement $a) => [
            'id' => 'announcement-'.$a->id,
            'type' => 'Announcement',
            'title' => $a->title,
            'body' => $a->content,
            'link' => null,
            'icon' => $a->type === 'warning' ? 'alert' : 'info',
            'read_at' => null,
            'created_at' => $a->created_at?->toIso8601String(),
            'source' => 'announcement',
        ]);

        $items = $announcements->concat($db)->sortByDesc('created_at')->values()->all();
        $unread = $user->unreadNotifications()->count() + $announcements->count();

        return ['items' => $items, 'unread_count' => $unread];
    }

    public function markRead(User $user, string $id): void
    {
        if (str_starts_with($id, 'announcement-')) {
            return;
        }

        $notification = $user->notifications()->where('id', $id)->first();
        $notification?->markAsRead();
    }

    public function markAllRead(User $user): void
    {
        $user->unreadNotifications->markAsRead();
    }

    public function notify(User $user, string $title, string $body, ?string $link = null, string $icon = 'bell'): void
    {
        $user->notify(new \App\Notifications\InAppNotification($title, $body, $link, $icon));
    }

    /** @return Collection<int, Announcement> */
    private function activeAnnouncements(): Collection
    {
        return Announcement::query()
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            })
            ->latest()
            ->limit(10)
            ->get();
    }
}
