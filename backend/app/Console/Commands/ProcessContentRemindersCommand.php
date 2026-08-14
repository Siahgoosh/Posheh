<?php

namespace App\Console\Commands;

use App\Services\ContentPlanner\ContentReminderService;
use Illuminate\Console\Command;

class ProcessContentRemindersCommand extends Command
{
    protected $signature = 'content-planner:process-reminders {--limit=50}';

    protected $description = 'Process due content planner SMS/in-app reminders';

    public function handle(ContentReminderService $reminders): int
    {
        $stats = $reminders->processDue((int) $this->option('limit'));
        $this->info(sprintf(
            'Content reminders — claimed:%d sent:%d failed:%d skipped:%d',
            $stats['claimed'],
            $stats['sent'],
            $stats['failed'],
            $stats['skipped']
        ));

        return self::SUCCESS;
    }
}
