<?php

namespace App\Console\Commands;

use App\Services\Seo\LocalSeoBootstrapService;
use App\Services\Seo\LocalSeoOpportunityService;
use App\Services\Seo\EntityGraphService;
use Illuminate\Console\Command;

class SeoLocalBootstrapCommand extends Command
{
    protected $signature = 'seo:local-bootstrap';

    protected $description = 'Bootstrap Posheh business/topic entities (no fake cities)';

    public function handle(LocalSeoBootstrapService $bootstrap): int
    {
        $result = $bootstrap->ensureDefaults();
        $this->info(json_encode($result, JSON_UNESCAPED_UNICODE));

        return ($result['ok'] ?? false) ? self::SUCCESS : self::FAILURE;
    }
}
