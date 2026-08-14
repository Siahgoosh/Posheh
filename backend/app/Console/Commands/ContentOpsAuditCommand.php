<?php

namespace App\Console\Commands;

use App\Services\ContentOps\ContentAiJobService;
use App\Services\ContentOps\ContentOpsBootstrapService;
use App\Services\ContentOps\ContentOpsDashboardService;
use App\Services\ContentOps\ContentRefreshEngine;
use Illuminate\Console\Command;

class ContentOpsAuditCommand extends Command
{
    protected $signature = 'content:ops-audit
        {--bootstrap : Ensure default task configs / style / limits}
        {--process=10 : Process queued AI jobs}
        {--weekly : Generate weekly report}
        {--monthly : Generate monthly report}';

    protected $description = 'Content OS daily/weekly automation (jobs, decay, reports)';

    public function handle(
        ContentOpsBootstrapService $bootstrap,
        ContentAiJobService $jobs,
        ContentRefreshEngine $refresh,
        ContentOpsDashboardService $dashboard,
    ): int {
        if ($this->option('bootstrap')) {
            $this->info(json_encode($bootstrap->ensureDefaults(), JSON_UNESCAPED_UNICODE));
        }

        try {
            $decay = $refresh->scanDecayAlerts();
            $this->info("Decay flagged: {$decay}");
        } catch (\Throwable $e) {
            // Never fail deploy/cron on optional SEO health schema drift
            $this->warn('Decay scan skipped: '.$e->getMessage());
        }

        try {
            $processed = $jobs->processQueued((int) $this->option('process'));
            $this->info("AI jobs processed: {$processed}");
        } catch (\Throwable $e) {
            $this->warn('AI job processing skipped: '.$e->getMessage());
        }

        if ($this->option('weekly')) {
            $this->info(json_encode($dashboard->buildWeeklyReport(), JSON_UNESCAPED_UNICODE));
        }
        if ($this->option('monthly')) {
            $this->info(json_encode($dashboard->buildMonthlyReport(), JSON_UNESCAPED_UNICODE));
        }

        return self::SUCCESS;
    }
}
