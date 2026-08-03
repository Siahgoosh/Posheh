<?php

namespace App\Console\Commands;

use App\Http\Controllers\SitemapController;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class GenerateSitemapCommand extends Command
{
    protected $signature = 'sitemap:generate';

    protected $description = 'Generate static sitemap XML files for nginx (GSC)';

    public function handle(): int
    {
        $controller = app(SitemapController::class);
        $targets = [
            'sitemap.xml' => $controller->index()->getContent(),
            'sitemap-pages.xml' => $controller->pages()->getContent(),
            'sitemap-blog.xml' => $controller->blog()->getContent(),
        ];

        foreach ($targets as $name => $xml) {
            foreach (['frontend/public', 'frontend/dist'] as $dir) {
                $path = base_path($dir.'/'.$name);
                File::ensureDirectoryExists(dirname($path));
                File::put($path, $xml);
            }
            $this->info("Wrote {$name} (".strlen($xml).' bytes)');
        }

        return self::SUCCESS;
    }
}
