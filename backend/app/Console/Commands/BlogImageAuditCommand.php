<?php

namespace App\Console\Commands;

use App\Services\BlogImages\ImageAuditService;
use App\Services\BlogImages\ImageBatchService;
use App\Services\BlogImages\ImageJobService;
use Illuminate\Console\Command;

class BlogImageAuditCommand extends Command
{
    protected $signature = 'blog:image-audit
        {--bootstrap : Seed provider/cost defaults}
        {--dry-run : Print dry-run plan for eligible articles}
        {--limit=400 : Dry-run article limit}
        {--process=0 : Process N queued image jobs}';

    protected $description = 'Audit blog images / dry-run generation / process queue';

    public function handle(ImageJobService $jobs, ImageAuditService $audit, ImageBatchService $batches): int
    {
        try {
            if ($this->option('bootstrap')) {
                $this->info(json_encode($jobs->bootstrap(), JSON_UNESCAPED_UNICODE));
            }
            $result = $audit->scanAll();
            $this->info(json_encode($result, JSON_UNESCAPED_UNICODE));
            if ($this->option('dry-run')) {
                $this->info(json_encode($batches->dryRun((int) $this->option('limit')), JSON_UNESCAPED_UNICODE));
            }
            $process = (int) $this->option('process');
            if ($process > 0) {
                $this->info('Processed: '.$jobs->processQueued($process));
            }
        } catch (\Throwable $e) {
            $this->warn('blog:image-audit soft-fail: '.$e->getMessage());
        }

        return self::SUCCESS;
    }
}
