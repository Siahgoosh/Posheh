<?php

namespace App\Services\BlogImages;

use App\Models\BlogPost;
use App\Models\Content\BlogImageAudit;
use App\Models\Content\BlogImageBatch;
use App\Models\Content\BlogImageJob;
use Illuminate\Support\Facades\Schema;

class ImageBatchService
{
    public function __construct(
        private readonly ImageAuditService $audit,
        private readonly ImageJobService $jobs,
        private readonly ImageProviderRegistry $providers,
    ) {}

    /** Dry run: scan + estimate without generating */
    public function dryRun(int $limit = 400, int $batchSize = 50): array
    {
        $this->audit->scanAll();
        $rows = BlogImageAudit::query()
            ->where('should_generate', true)
            ->orderByRaw("CASE priority WHEN 'P0' THEN 1 WHEN 'P1' THEN 2 WHEN 'P2' THEN 3 ELSE 4 END")
            ->orderByDesc('opportunity_score')
            ->limit($limit)
            ->with('post:id,title,slug,is_published')
            ->get();

        $provider = $this->providers->get();
        $costEach = 0;
        if (Schema::hasTable('blog_image_provider_configs')) {
            $cfg = \App\Models\Content\BlogImageProviderConfig::query()->where('key', $provider->key())->first();
            $costEach = (int) ($cfg->cost_per_image_toman ?? 0);
        }

        $items = $rows->map(fn (BlogImageAudit $a) => [
            'blog_post_id' => $a->blog_post_id,
            'title' => $a->post?->title,
            'slug' => $a->post?->slug,
            'priority' => $a->priority,
            'image_type' => $a->recommended_type,
            'image_status' => $a->image_status,
            'opportunity_score' => $a->opportunity_score,
            'estimated_cost_toman' => $costEach,
        ])->all();

        return [
            'provider' => $provider->key(),
            'count' => count($items),
            'batch_size' => $batchSize,
            'batches_needed' => (int) ceil(max(1, count($items)) / max(1, $batchSize)),
            'estimated_total_cost_toman' => $costEach * count($items),
            'items' => $items,
            'note' => 'DRY RUN only — no images generated. Human confirmation required for mass generation.',
        ];
    }

    public function createBatchFromDryRun(array $dryRun, ?int $userId, bool $confirm = false): BlogImageBatch
    {
        if (! $confirm) {
            throw new \RuntimeException('Explicit confirmation required for mass image generation');
        }
        $budget = $this->jobs->budgetAllows((int) ($dryRun['estimated_total_cost_toman'] ?? 0));
        if (! $budget['allowed']) {
            throw new \RuntimeException($budget['reason'] ?? 'Budget blocked');
        }

        $batch = BlogImageBatch::create([
            'name' => 'Image batch '.now()->toDateTimeString(),
            'status' => 'running',
            'batch_size' => (int) ($dryRun['batch_size'] ?? 50),
            'total' => (int) ($dryRun['count'] ?? 0),
            'estimated_cost_toman' => (int) ($dryRun['estimated_total_cost_toman'] ?? 0),
            'provider' => $dryRun['provider'] ?? null,
            'dry_run' => false,
            'created_by' => $userId,
            'confirmed_at' => now(),
            'filters' => ['from_dry_run' => true],
        ]);

        foreach ($dryRun['items'] ?? [] as $item) {
            $post = BlogPost::find($item['blog_post_id'] ?? 0);
            if (! $post) {
                continue;
            }
            try {
                $this->jobs->enqueueForPost($post, $userId, $batch->id);
            } catch (\Throwable $e) {
                $batch->increment('failed');
            }
        }

        return $batch->fresh();
    }

    public function pause(int $batchId): BlogImageBatch
    {
        $b = BlogImageBatch::findOrFail($batchId);
        $b->update(['status' => 'paused']);

        return $b;
    }

    public function resume(int $batchId): BlogImageBatch
    {
        $b = BlogImageBatch::findOrFail($batchId);
        $b->update(['status' => 'running']);

        return $b;
    }

    public function cancel(int $batchId): BlogImageBatch
    {
        $b = BlogImageBatch::findOrFail($batchId);
        $b->update(['status' => 'cancelled']);
        BlogImageJob::query()->where('batch_id', $batchId)->whereIn('status', ['queued', 'retrying'])->update(['status' => 'cancelled']);

        return $b;
    }
}
