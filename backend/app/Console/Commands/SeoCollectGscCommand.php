<?php

namespace App\Console\Commands;

use App\Services\Seo\SeoGscCollector;
use App\Services\Seo\SeoOpportunityEngine;
use Illuminate\Console\Command;

class SeoCollectGscCommand extends Command
{
    protected $signature = 'seo:collect-gsc {--days=3 : Lookback days ending yesterday}';

    protected $description = 'Collect Google Search Console metrics (no fake data if unavailable)';

    public function handle(SeoGscCollector $collector): int
    {
        $days = max(1, (int) $this->option('days'));
        $end = now()->subDay()->endOfDay();
        $start = (clone $end)->subDays($days - 1)->startOfDay();
        $result = $collector->collect($start, $end);
        $this->info($result['status'].': '.$result['message']);

        return $result['status'] === 'OK' ? self::SUCCESS : self::SUCCESS;
    }
}
