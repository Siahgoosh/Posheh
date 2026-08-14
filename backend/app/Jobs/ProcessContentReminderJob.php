<?php

namespace App\Jobs;

use App\Services\ContentPlanner\ContentReminderService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessContentReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public readonly int $reminderId) {}

    public function handle(ContentReminderService $reminders): void
    {
        $claimed = $reminders->claim($this->reminderId);
        if (! $claimed) {
            return;
        }
        $reminders->send($claimed);
    }
}
