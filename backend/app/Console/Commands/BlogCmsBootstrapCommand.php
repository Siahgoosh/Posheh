<?php

namespace App\Console\Commands;

use App\Services\Blog\BlogCmsBootstrapService;
use Illuminate\Console\Command;

class BlogCmsBootstrapCommand extends Command
{
    protected $signature = 'blog:cms-bootstrap';

    protected $description = 'Seed default blog categories and canonical author';

    public function handle(BlogCmsBootstrapService $bootstrap): int
    {
        $result = $bootstrap->ensureDefaults();
        if (! ($result['ok'] ?? false)) {
            $this->error($result['message_fa'] ?? 'Blog CMS bootstrap failed');

            return self::FAILURE;
        }
        $this->info($result['message_fa'] ?? 'Blog CMS defaults ready.');

        return self::SUCCESS;
    }
}
