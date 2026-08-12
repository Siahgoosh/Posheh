<?php

namespace App\Services\BlogImages;

use App\Models\BlogPost;
use App\Models\Content\BlogImageAudit;
use App\Models\Content\BlogImageBatch;
use App\Models\Content\BlogImageCostLimit;
use App\Models\Content\BlogImageJob;
use App\Models\Content\BlogImageProviderConfig;
use App\Services\Blog\BlogSitemapService;
use App\Services\ContentOps\PromptSecurityService;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageJobService
{
    public function __construct(
        private readonly ImageProviderRegistry $providers,
        private readonly ImageBriefBuilder $briefs,
        private readonly ImageAuditService $audit,
        private readonly PromptSecurityService $security,
        private readonly BlogSitemapService $sitemap,
    ) {}

    public function bootstrap(): array
    {
        if (! Schema::hasTable('blog_image_provider_configs')) {
            return ['ok' => false, 'message_fa' => 'جداول تصاویر وبلاگ موجود نیست — ابتدا migrate را اجرا کنید.', 'message' => 'Migration required'];
        }
        try {
        BlogImageProviderConfig::query()->firstOrCreate(
            ['key' => 'mock'],
            ['label' => 'Mock SVG', 'model' => 'mock-svg-v1', 'is_active' => true, 'cost_per_image_toman' => 0]
        );
        BlogImageProviderConfig::query()->firstOrCreate(
            ['key' => 'openai'],
            [
                'label' => 'OpenAI Images',
                'model' => config('blog_images.openai_model'),
                'is_active' => (bool) config('blog_images.openai_enabled'),
                'cost_per_image_toman' => (int) config('blog_images.openai_cost_toman'),
            ]
        );
        if (Schema::hasTable('blog_image_cost_limits')) {
            foreach (['per_image' => 0, 'per_article' => 0, 'daily' => 0, 'monthly' => 0] as $scope => $limit) {
                BlogImageCostLimit::query()->firstOrCreate(
                    ['scope' => $scope],
                    ['limit_toman' => $limit, 'limit_count' => 0, 'is_active' => false]
                );
            }
        }

        return [
            'ok' => true,
            'message_fa' => 'ارائه‌دهندگان تصویر و سقف بودجه پیش‌فرض آماده شد.',
            'providers' => $this->providers->list(),
        ];
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'message_fa' => 'خطا در راه‌اندازی تصاویر: '.$e->getMessage(),
            ];
        }
    }

    /** @return array{allowed:bool,reason:?string} */
    public function budgetAllows(int $estimated = 0): array
    {
        if (! Schema::hasTable('blog_image_cost_limits')) {
            return ['allowed' => true, 'reason' => null];
        }
        if (config('blog_images.require_budget') && ! BlogImageCostLimit::query()->where('is_active', true)->exists()) {
            // Soft allow for mock (zero cost); block paid providers without budget
            $provider = $this->providers->get();
            if ($provider->key() !== 'mock' && $estimated > 0) {
                return ['allowed' => false, 'reason' => 'Budget not configured — activate blog_image_cost_limits before paid generation'];
            }
        }

        foreach (['daily' => [now()->startOfDay(), now()->endOfDay()], 'monthly' => [now()->startOfMonth(), now()->endOfMonth()]] as $scope => [$from, $to]) {
            $limit = BlogImageCostLimit::query()->where('scope', $scope)->where('is_active', true)->first();
            if (! $limit) {
                continue;
            }
            if ((int) $limit->limit_toman > 0) {
                $spent = (int) BlogImageJob::query()->whereBetween('created_at', [$from, $to])->sum('actual_cost_toman');
                if ($spent + $estimated > (int) $limit->limit_toman) {
                    return ['allowed' => false, 'reason' => "Image {$scope} budget exceeded"];
                }
            }
            if ((int) $limit->limit_count > 0) {
                $count = (int) BlogImageJob::query()->whereBetween('created_at', [$from, $to])->whereIn('status', ['generated', 'approved', 'published', 'processing', 'queued'])->count();
                if ($count + 1 > (int) $limit->limit_count) {
                    return ['allowed' => false, 'reason' => "Image {$scope} count limit exceeded"];
                }
            }
        }

        return ['allowed' => true, 'reason' => null];
    }

    public function enqueueForPost(BlogPost $post, ?int $userId = null, ?int $batchId = null, string $variant = 'A'): BlogImageJob
    {
        $audit = $this->audit->auditPost($post);
        if (! ($audit['should_generate'] ?? false)) {
            throw new \RuntimeException('Generation skipped: '.($audit['skip_reason'] ?? 'not eligible'));
        }

        $type = (string) ($audit['recommended_type'] ?? 'HERO');
        $brief = $this->briefs->build($post, $type);
        $provider = $this->providers->get();
        $cfg = Schema::hasTable('blog_image_provider_configs')
            ? BlogImageProviderConfig::query()->where('key', $provider->key())->first()
            : null;
        $est = (int) ($cfg->cost_per_image_toman ?? 0);
        $budget = $this->budgetAllows($est);
        if (! $budget['allowed']) {
            throw new \RuntimeException($budget['reason'] ?? 'Budget blocked');
        }

        $key = hash('sha256', $post->id.'|'.$type.'|'.$variant.'|'.ImageBriefBuilder::PROMPT_VERSION);

        return BlogImageJob::query()->firstOrCreate(
            ['idempotency_key' => $key],
            [
                'blog_post_id' => $post->id,
                'batch_id' => $batchId,
                'created_by' => $userId,
                'status' => 'queued',
                'priority' => $audit['priority'] ?? 'P2',
                'opportunity_score' => $audit['opportunity_score'] ?? 0,
                'image_status' => $audit['image_status'] ?? null,
                'image_type' => $type,
                'provider' => $provider->key(),
                'model' => $cfg->model ?? $provider->key(),
                'brief' => $brief,
                'prompt' => $brief['prompt'],
                'negative_prompt' => $brief['negative_prompt'],
                'prompt_version' => ImageBriefBuilder::PROMPT_VERSION,
                'variant' => $variant,
                'alt_text' => $brief['alt_text'],
                'seo_filename' => $brief['filename'],
                'is_illustrative' => true,
                'is_ai_generated' => true,
                'auto_approve' => (bool) config('blog_images.auto_approve', false),
                'estimated_cost_toman' => $est,
            ]
        );
    }

    public function process(BlogImageJob $job): BlogImageJob
    {
        if (in_array($job->status, ['published', 'approved', 'cancelled'], true) && $job->public_url) {
            return $job;
        }
        $job->status = 'processing';
        $job->started_at = now();
        $job->save();

        try {
            $provider = $this->providers->get($job->provider);
            $result = $provider->generate([
                'prompt' => $this->security->sanitizeUntrusted((string) $job->prompt),
                'negative_prompt' => (string) $job->negative_prompt,
                'model' => $job->model,
                'resolution' => data_get($job->brief, 'resolution') ?: config('blog_images.default_resolution'),
                'timeout' => 90,
            ]);

            if (! ($result['ok'] ?? false) || empty($result['binary'])) {
                // fallback once
                $fallbackKey = (string) config('blog_images.fallback_provider', 'mock');
                if ($provider->key() !== $fallbackKey) {
                    $fb = $this->providers->get($fallbackKey);
                    $result = $fb->generate([
                        'prompt' => (string) $job->prompt,
                        'model' => 'mock-svg-v1',
                    ]);
                    $job->provider = $fb->key();
                }
            }

            if (! ($result['ok'] ?? false) || empty($result['binary'])) {
                throw new \RuntimeException($result['error'] ?? 'Image generation failed');
            }

            $ext = ($result['mime'] ?? '') === 'image/svg+xml' ? 'svg' : 'png';
            $filename = ($job->seo_filename ? pathinfo($job->seo_filename, PATHINFO_FILENAME) : 'blog-'.$job->blog_post_id)
                .'-'.Str::lower($job->variant).'.'.$ext;
            $path = 'blog/ai-images/'.now()->format('Y/m').'/'.$filename;
            Storage::disk((string) config('blog_images.storage_disk', 'public'))->put($path, $result['binary']);

            $url = Storage::disk((string) config('blog_images.storage_disk', 'public'))->url($path);

            $job->fill([
                'status' => 'generated',
                'storage_path' => $path,
                'public_url' => $url,
                'width' => $result['width'] ?? null,
                'height' => $result['height'] ?? null,
                'bytes' => strlen((string) $result['binary']),
                'mime' => $result['mime'] ?? null,
                'format' => $ext,
                'model' => $result['model'] ?? $job->model,
                'actual_cost_toman' => (int) ($result['cost_toman'] ?? $job->estimated_cost_toman),
                'finished_at' => now(),
                'error' => null,
                'quality' => [
                    'relevance' => 70,
                    'brand_fit' => 70,
                    'note' => 'Human review required before publish',
                ],
            ]);
            $job->save();

            if ($job->auto_approve || config('blog_images.auto_approve')) {
                return $this->approve($job, null, auto: true);
            }

            return $job->fresh();
        } catch (\Throwable $e) {
            return $this->failOrRetry($job, $e->getMessage());
        }
    }

    public function processQueued(int $limit = 10): int
    {
        if (! Schema::hasTable('blog_image_jobs')) {
            return 0;
        }
        // Respect paused batches
        $jobs = BlogImageJob::query()
            ->where(function ($q) {
                $q->where('status', 'queued')
                    ->orWhere(function ($qq) {
                        $qq->where('status', 'retrying')
                            ->where(function ($q3) {
                                $q3->whereNull('next_retry_at')->orWhere('next_retry_at', '<=', now());
                            });
                    });
            })
            ->where(function ($q) {
                $q->whereNull('batch_id')
                    ->orWhereIn('batch_id', function ($sub) {
                        $sub->select('id')->from('blog_image_batches')->whereIn('status', ['running', 'confirmed', 'completed']);
                    });
            })
            ->orderByRaw("CASE priority WHEN 'P0' THEN 1 WHEN 'P1' THEN 2 WHEN 'P2' THEN 3 ELSE 4 END")
            ->orderBy('id')
            ->limit($limit)
            ->get();

        $n = 0;
        foreach ($jobs as $job) {
            // skip if parent batch paused/cancelled
            if ($job->batch_id && Schema::hasTable('blog_image_batches')) {
                $batch = BlogImageBatch::find($job->batch_id);
                if ($batch && in_array($batch->status, ['paused', 'cancelled', 'draft'], true)) {
                    continue;
                }
            }
            $this->process($job);
            $n++;
        }

        return $n;
    }

    public function approve(BlogImageJob $job, ?int $userId, bool $auto = false): BlogImageJob
    {
        if (! $job->public_url) {
            throw new \RuntimeException('No generated image to approve');
        }
        $post = BlogPost::findOrFail($job->blog_post_id);
        $previous = $post->cover_image;

        $post->cover_image = $job->public_url;
        if (Schema::hasColumn('blog_posts', 'cover_alt')) {
            $post->cover_alt = $job->alt_text;
            $post->cover_is_ai_generated = true;
            $post->cover_is_illustrative = true;
        }
        $post->save();

        $job->status = 'published';
        $job->reviewed_by = $userId;
        $job->reviewed_at = now();
        $job->previous_media_id = null;
        $job->save();

        if ($post->is_published) {
            $this->sitemap->invalidate();
        }

        // update batch counters
        if ($job->batch_id && Schema::hasTable('blog_image_batches')) {
            BlogImageBatch::query()->where('id', $job->batch_id)->increment('success');
            BlogImageBatch::query()->where('id', $job->batch_id)->increment('processed');
            BlogImageBatch::query()->where('id', $job->batch_id)->increment('actual_cost_toman', (int) $job->actual_cost_toman);
        }

        return $job->fresh();
    }

    public function reject(BlogImageJob $job, int $userId, string $reason, ?string $note = null): BlogImageJob
    {
        $job->status = 'rejected';
        $job->reject_reason = $reason;
        $job->reject_note = $note;
        $job->reviewed_by = $userId;
        $job->reviewed_at = now();
        $job->save();
        if ($job->batch_id) {
            BlogImageBatch::query()->where('id', $job->batch_id)->increment('rejected');
            BlogImageBatch::query()->where('id', $job->batch_id)->increment('processed');
        }

        return $job;
    }

    public function regenerate(BlogImageJob $job, ?int $userId = null): BlogImageJob
    {
        $post = BlogPost::findOrFail($job->blog_post_id);
        $variant = chr(ord($job->variant ?: 'A') + 1);
        if ($variant > 'C') {
            $variant = 'A';
        }
        $brief = $this->briefs->build($post, $job->image_type ?: 'HERO');
        if ($job->reject_reason) {
            $brief['prompt'] .= "\nAVOID_PREVIOUS_ISSUE: ".$job->reject_reason.' '.($job->reject_note ?? '');
        }
        $new = $this->enqueueForPost($post, $userId, $job->batch_id, $variant);
        $new->brief = $brief;
        $new->prompt = $brief['prompt'];
        $new->save();

        return $this->process($new);
    }

    private function failOrRetry(BlogImageJob $job, string $error): BlogImageJob
    {
        $job->retry_count = (int) $job->retry_count + 1;
        $job->error = $this->security->redactSecrets($error);
        $max = (int) config('blog_images.max_retries', 3);
        if ($job->retry_count <= $max) {
            $job->status = 'retrying';
            $job->next_retry_at = now()->addSeconds(min(3600, (2 ** $job->retry_count) * 30));
        } else {
            $job->status = 'failed';
            $job->finished_at = now();
            if ($job->batch_id) {
                BlogImageBatch::query()->where('id', $job->batch_id)->increment('failed');
                BlogImageBatch::query()->where('id', $job->batch_id)->increment('processed');
            }
        }
        $job->save();

        return $job->fresh();
    }
}
