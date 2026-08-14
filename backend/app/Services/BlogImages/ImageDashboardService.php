<?php

namespace App\Services\BlogImages;

use App\Models\Content\BlogImageAudit;
use App\Models\Content\BlogImageBatch;
use App\Models\Content\BlogImageJob;
use Illuminate\Support\Facades\Schema;

class ImageDashboardService
{
    public function __construct(
        private readonly ImageProviderRegistry $providers,
    ) {}

    /** @return array<string,mixed> */
    public function executive(): array
    {
        if (! Schema::hasTable('blog_image_audits')) {
            return ['note' => 'Run migration 2026_08_12_180000'];
        }

        $byStatus = BlogImageAudit::query()
            ->selectRaw('image_status, count(*) as c')
            ->groupBy('image_status')
            ->pluck('c', 'image_status');

        return [
            'audit' => [
                'by_status' => $byStatus,
                'should_generate' => BlogImageAudit::query()->where('should_generate', true)->count(),
                'no_image' => BlogImageAudit::query()->where('image_status', 'NO_IMAGE')->count(),
                'hero_missing' => BlogImageAudit::query()->where('image_status', 'HERO_MISSING')->count(),
            ],
            'jobs' => [
                'queued' => BlogImageJob::query()->where('status', 'queued')->count(),
                'processing' => BlogImageJob::query()->where('status', 'processing')->count(),
                'generated' => BlogImageJob::query()->where('status', 'generated')->count(),
                'approved_or_published' => BlogImageJob::query()->whereIn('status', ['approved', 'published'])->count(),
                'rejected' => BlogImageJob::query()->where('status', 'rejected')->count(),
                'failed' => BlogImageJob::query()->where('status', 'failed')->count(),
                'cost_toman' => (int) BlogImageJob::query()->sum('actual_cost_toman'),
            ],
            'batches' => BlogImageBatch::query()->orderByDesc('id')->limit(10)->get(),
            'providers' => $this->providers->list(),
            'policies' => [
                'auto_approve' => (bool) config('blog_images.auto_approve', false),
                'require_budget' => (bool) config('blog_images.require_budget', true),
                'default_provider' => config('blog_images.default_provider'),
            ],
            'note' => 'Images are illustrative by default. Human approval required before publish. Not a Google Score.',
        ];
    }
}
