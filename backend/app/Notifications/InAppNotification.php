<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class InAppNotification extends Notification
{
    public function __construct(
        private readonly string $title,
        private readonly string $body,
        private readonly ?string $link = null,
        private readonly string $icon = 'bell',
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'body' => $this->body,
            'link' => $this->link,
            'icon' => $this->icon,
        ];
    }
}
