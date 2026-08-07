<?php

namespace App\Console\Commands;

use App\Modules\Communication\Application\Services\LiveVisitorService;
use Illuminate\Console\Command;

class CommMarkStaleVisitorsCommand extends Command
{
    protected $signature = 'communication:mark-stale-visitors';

    protected $description = 'Mark inactive visitor sessions as offline';

    public function handle(LiveVisitorService $live): int
    {
        $count = $live->markStaleOffline();
        $this->info("Marked {$count} sessions offline.");

        return self::SUCCESS;
    }
}
