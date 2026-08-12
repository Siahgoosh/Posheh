<?php

namespace App\Console\Commands;

use App\Services\Seo\EntityGraphService;
use App\Services\Seo\LocalSeoOpportunityService;
use App\Services\Blog\BlogSitemapService;
use App\Models\Seo\SeoLocation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class SeoLocalAuditCommand extends Command
{
    protected $signature = 'seo:local-audit {--opportunities : Regenerate weekly local opportunities}';

    protected $description = 'Local SEO NAP/entity consistency + optional weekly opportunities';

    public function handle(EntityGraphService $graph, LocalSeoOpportunityService $opps, BlogSitemapService $sitemap): int
    {
        $warnings = $graph->napConsistencyWarnings();
        foreach ($warnings as $w) {
            $this->warn("[{$w['severity']}] {$w['code']}: {$w['message']}");
        }

        if (Schema::hasTable('seo_locations')) {
            $published = SeoLocation::published()->count();
            $this->info("Published indexable locations: {$published}");
        }

        if ($this->option('opportunities')) {
            $rows = $opps->generateWeekly();
            $this->info('Opportunities generated: '.count($rows));
        }

        $sitemap->invalidate();
        $this->info('Sitemap cache invalidated.');

        $critical = collect($warnings)->where('severity', 'critical')->count();

        return $critical > 0 ? self::FAILURE : self::SUCCESS;
    }
}
