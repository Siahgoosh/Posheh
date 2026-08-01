<?php

namespace App\Console\Commands;

use App\Models\BlogPost;
use App\Services\Blog\BlogArticleGenerator;
use Illuminate\Console\Command;

class SeedBlogArticlesCommand extends Command
{
    protected $signature = 'blog:seed {--count=300 : Number of articles} {--force : Run without confirmation}';

    protected $description = 'Seed professional SEO blog articles (500+ words, H2/H3, internal links, images)';

    public function handle(BlogArticleGenerator $generator): int
    {
        $count = max(1, min(500, (int) $this->option('count')));

        if (! $this->option('force') && ! $this->confirm("Seed {$count} blog articles? Existing slugs will be updated.")) {
            return self::SUCCESS;
        }

        $this->info("Generating up to {$count} articles (12 pillars + category articles)...");
        $articles = array_merge(
            $generator->pillarArticles(),
            $generator->generate(max(0, $count - 12)),
        );
        $bar = $this->output->createProgressBar(count($articles));
        $bar->start();

        $minWords = PHP_INT_MAX;
        $maxWords = 0;

        $validSlugs = [];

        foreach ($articles as $data) {
            BlogPost::updateOrCreate(
                ['slug' => $data['slug']],
                $data,
            );
            $validSlugs[] = $data['slug'];

            $words = count(preg_split('/\s+/u', trim(strip_tags($data['content'])), -1, PREG_SPLIT_NO_EMPTY));
            $minWords = min($minWords, $words);
            $maxWords = max($maxWords, $words);
            $bar->advance();
        }

        $removed = BlogPost::query()
            ->where('author_name', 'تیم پوشه')
            ->whereNotIn('slug', $validSlugs)
            ->delete();

        $bar->finish();
        $this->newLine(2);
        if ($removed > 0) {
            $this->warn("Removed {$removed} outdated auto-generated posts (stale slugs).");
        }
        $total = BlogPost::where('is_published', true)->count();
        $this->info("Done. Published posts in DB: {$total}");
        $this->info("Word count range (approx): {$minWords} – {$maxWords}");

        return self::SUCCESS;
    }
}
