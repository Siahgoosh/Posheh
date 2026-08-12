<?php

namespace App\Console\Commands;

use App\Http\Controllers\SitemapController;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Writes static sitemap XML files into Laravel public/ for nginx try_files
 * and documents the live dynamic routes. Prefer live routes in production;
 * this command is optional caching for CDN/ops.
 */
class SitemapGenerateCommand extends Command
{
    protected $signature = 'sitemap:generate {--force : Overwrite without confirmation}';

    protected $description = 'Generate sitemap XML files into public/ (pages, blog, tours, index)';

    public function handle(): int
    {
        $controller = app(SitemapController::class);
        $map = [
            'sitemap.xml' => $controller->xml()->getContent(),
            'sitemap-index.xml' => $controller->index()->getContent(),
            'sitemap-pages.xml' => $controller->pages()->getContent(),
            'sitemap-blog.xml' => $controller->blog()->getContent(),
            'sitemap-tours.xml' => $controller->tours()->getContent(),
        ];

        $dir = public_path();
        foreach ($map as $file => $content) {
            File::put($dir.DIRECTORY_SEPARATOR.$file, $content);
            $this->line("Wrote public/{$file}");
        }

        $this->info('Sitemaps generated. Prefer live routes: /sitemap.xml /sitemap-blog.xml …');

        return self::SUCCESS;
    }
}
