<?php

namespace App\Console\Commands;

use App\Models\BlogPost;
use App\Services\Blog\BlogContentQualityScorer;
use App\Services\Blog\BlogQualityGate;
use App\Services\Blog\BlogVersioningService;
use App\Services\Blog\Rebuild\Batch1RebuiltArticles;
use Illuminate\Console\Command;

class SeedBlogRebuildBatchCommand extends Command
{
    protected $signature = 'blog:rebuild-batch {batch=1 : Batch number} {--force : Skip confirmation}';

    protected $description = 'Seed rebuilt blog articles as drafts (never auto-publish)';

    public function handle(
        BlogContentQualityScorer $scorer,
        BlogQualityGate $gate,
        BlogVersioningService $versions,
    ): int {
        $batch = (int) $this->argument('batch');
        if ($batch !== 1) {
            $this->error('Only batch 1 is implemented in this release.');

            return self::FAILURE;
        }

        if (! $this->option('force') && ! $this->confirm('Import batch 1 rebuilt articles as drafts (is_published=false)?')) {
            return self::SUCCESS;
        }

        $articles = (new Batch1RebuiltArticles)->all();
        $rows = [];

        foreach ($articles as $data) {
            $beforePayload = BlogPost::query()->where('slug', $data['slug'])->first();
            $beforeScore = $scorer->score($beforePayload ? $beforePayload->toArray() : [
                'title' => $data['title'],
                'content' => '<p>thin</p>',
                'meta_title' => '',
                'meta_description' => '',
            ]);

            $scores = $scorer->score($data);
            $gateResult = $gate->evaluate($data, forPublish: false);
            $data['quality_scores'] = [
                'before' => $beforeScore,
                'after' => $scores,
                'gate' => [
                    'passed' => $gateResult['passed'],
                    'warnings' => $gateResult['warnings'],
                    'blockers' => $gateResult['blockers'],
                ],
                'batch' => 1,
            ];

            /** @var BlogPost $post */
            $post = BlogPost::updateOrCreate(['slug' => $data['slug']], $data);
            $versions->snapshot($post, 'rebuild-batch-1', 'system');

            $rows[] = [
                $post->slug,
                $beforeScore['overall'],
                $scores['overall'],
                $scores['grade'],
                $post->review_status,
                $post->is_published ? 'yes' : 'draft',
            ];
        }

        $this->table(['slug', 'before', 'after', 'grade', 'review', 'published'], $rows);
        $this->info('Batch 1 imported as drafts with rebuild_locked=true. Do not publish without approval.');

        return self::SUCCESS;
    }
}
