<?php

namespace App\Console\Commands;

use App\Services\Seo\SeoGrowthDashboardService;
use App\Services\Seo\SeoOpportunityEngine;
use Illuminate\Console\Command;

class SeoAnalyzeCommand extends Command
{
    protected $signature = 'seo:analyze {--weekly-report : Also store weekly snapshot}';

    protected $description = 'Run SEO opportunity/decay/topic analysis from first-party data';

    public function handle(SeoOpportunityEngine $engine, SeoGrowthDashboardService $dashboard): int
    {
        $stats = $engine->analyze();
        $this->table(['metric', 'count'], collect($stats)->map(fn ($v, $k) => [$k, $v])->values()->all());
        if ($this->option('weekly-report')) {
            $dashboard->buildWeeklyReport();
            $this->info('Weekly report snapshot stored.');
        }

        return self::SUCCESS;
    }
}
