<?php

namespace App\Console\Commands;

use App\Models\CrmNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class PurgeOldNotificationsCommand extends Command
{
    protected $signature = 'notifications:purge-old {--hours=48}';

    protected $description = 'Delete in-app notifications older than N hours (default 48)';

    public function handle(): int
    {
        if (! Schema::hasTable('crm_notifications')) {
            $this->warn('crm_notifications table missing');

            return self::SUCCESS;
        }

        $hours = max(1, (int) $this->option('hours'));
        $cutoff = now()->subHours($hours);
        $deleted = CrmNotification::where('created_at', '<', $cutoff)->delete();
        $this->info("Deleted {$deleted} notifications older than {$hours}h.");

        return self::SUCCESS;
    }
}
