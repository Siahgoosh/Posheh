<?php

namespace App\Console\Commands;

use App\Services\Cro\CroBootstrapService;
use Illuminate\Console\Command;

class CroBootstrapCommand extends Command
{
    protected $signature = 'cro:bootstrap';

    protected $description = 'Seed default soft CTAs, rules, and page goals for CRO system';

    public function handle(CroBootstrapService $bootstrap): int
    {
        $bootstrap->ensureDefaults();
        $this->info('CRO defaults ensured.');

        return self::SUCCESS;
    }
}
