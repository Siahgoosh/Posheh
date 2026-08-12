<?php

namespace App\Console\Commands;

use App\Models\BlogPost;
use App\Services\Blog\BlogContentQualityScorer;
use App\Services\Blog\BlogQualityGate;
use App\Services\Blog\BlogVersioningService;
use App\Services\Blog\Rebuild\Batch1RebuiltArticles;
use App\Services\Blog\Rebuild\Phase4Batch1RebuiltArticles;
use Illuminate\Console\Command;

class SeedBlogRebuildBatchCommand extends Command
{
    protected $signature = 'blog:rebuild-batch {batch=1 : Batch number (1=first five, 2=phase4 next five)} {--force : Skip confirmation}';

    protected $description = 'Seed rebuilt blog articles as drafts (never auto-publish)';

    public function handle(
        BlogContentQualityScorer $scorer,
        BlogQualityGate $gate,
        BlogVersioningService $versions,
    ): int {
        $batch = (int) $this->argument('batch');
        $articles = match ($batch) {
            1 => (new Batch1RebuiltArticles)->all(),
            2 => (new Phase4Batch1RebuiltArticles)->all(),
            default => null,
        };

        if ($articles === null) {
            $this->error('Supported batches: 1 (CONTENT-REBUILD-BATCH-1), 2 (PHASE-4 next five).');

            return self::FAILURE;
        }

        if (! $this->option('force') && ! $this->confirm("Import batch {$batch} rebuilt articles as drafts (is_published=false)?")) {
            return self::SUCCESS;
        }

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
            if (! $gateResult['passed']) {
                $this->warn("Quality gate warnings/blockers for {$data['slug']}: ".implode('; ', array_merge($gateResult['blockers'], $gateResult['warnings'])));
            }

            $data['quality_scores'] = [
                'before' => $beforeScore,
                'after' => $scores,
                'gate' => [
                    'passed' => $gateResult['passed'],
                    'warnings' => $gateResult['warnings'],
                    'blockers' => $gateResult['blockers'],
                ],
                'batch' => $batch,
            ];

            /** @var BlogPost $post */
            $post = BlogPost::updateOrCreate(['slug' => $data['slug']], $data);
            $versions->snapshot($post, 'rebuild-batch-'.$batch, 'system');

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
        $this->info("Batch {$batch} imported as drafts with rebuild_locked=true. Do not publish without approval.");

        return self::SUCCESS;
    }
}
