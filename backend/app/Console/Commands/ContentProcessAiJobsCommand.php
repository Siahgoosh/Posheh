<?php

namespace App\Console\Commands;

use App\Services\ContentOps\ContentAiJobService;
use Illuminate\Console\Command;

class ContentProcessAiJobsCommand extends Command
{
    protected $signature = 'content:process-ai-jobs {--limit=20}';

    protected $description = 'Process queued Content OS AI jobs asynchronously';

    public function handle(ContentAiJobService $jobs): int
    {
        $n = $jobs->processQueued((int) $this->option('limit'));
        $this->info("Processed {$n} jobs");

        return self::SUCCESS;
    }
}
