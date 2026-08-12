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
        $result = $bootstrap->ensureDefaults();
        if (! ($result['ok'] ?? false)) {
            $this->error($result['message_fa'] ?? 'CRO bootstrap failed');

            return self::FAILURE;
        }
        $this->info($result['message_fa'] ?? 'CRO defaults ensured.');

        return self::SUCCESS;
    }
}
