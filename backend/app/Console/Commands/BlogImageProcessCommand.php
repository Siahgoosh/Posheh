<?php

namespace App\Console\Commands;

use App\Services\BlogImages\ImageJobService;
use Illuminate\Console\Command;

class BlogImageProcessCommand extends Command
{
    protected $signature = 'blog:image-process {--limit=10}';

    protected $description = 'Process queued blog AI image jobs asynchronously';

    public function handle(ImageJobService $jobs): int
    {
        try {
            $n = $jobs->processQueued((int) $this->option('limit'));
            $this->info("Processed {$n} image jobs");
        } catch (\Throwable $e) {
            $this->warn($e->getMessage());
        }

        return self::SUCCESS;
    }
}
