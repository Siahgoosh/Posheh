<?php

namespace App\Services\ContentOps;

use App\Models\BlogPost;
use App\Models\Content\ContentAiJob;
use App\Models\Content\ContentAiTaskConfig;
use App\Models\Content\ContentImageBrief;
use App\Services\Blog\BlogAiAssistantService;
use App\Services\Blog\BlogContentQualityScorer;
use App\Services\Blog\BlogQualityGate;
use Illuminate\Support\Facades\Schema;

class ContentAiJobService
{
    public function __construct(
        private readonly ContentAiProviderRegistry $registry,
        private readonly ContentAiCostControlService $costs,
        private readonly PromptSecurityService $security,
        private readonly AiOutputSanitizer $sanitizer,
        private readonly BlogAiAssistantService $assistant,
        private readonly FactCheckEngine $facts,
        private readonly InternalLinkEngine $links,
        private readonly ContentRefreshEngine $refresh,
        private readonly RepurposeEngine $repurpose,
        private readonly BlogContentQualityScorer $quality,
        private readonly BlogQualityGate $gate,
    ) {}

    /**
     * @param  array<string,mixed>  $input
     */
    public function enqueue(string $type, array $input, ?int $blogPostId = null, ?int $userId = null, ?string $idempotencyKey = null): ContentAiJob
    {
        if (! in_array($type, ContentAiJob::TYPES, true)) {
            throw new \InvalidArgumentException('Invalid job type');
        }

        $key = $idempotencyKey ?: hash('sha256', $type.'|'.($blogPostId ?? 0).'|'.json_encode($input));
        $existing = ContentAiJob::query()->where('idempotency_key', $key)->first();
        if ($existing) {
            return $existing;
        }

        $limit = $this->costs->checkLimits($userId, $blogPostId);
        if (! $limit['allowed']) {
            throw new \RuntimeException($limit['reason'] ?? 'AI cost limit exceeded');
        }

        $config = Schema::hasTable('content_ai_task_configs')
            ? ContentAiTaskConfig::query()->where('task_key', $type)->where('is_active', true)->first()
            : null;

        return ContentAiJob::create([
            'idempotency_key' => $key,
            'type' => $type,
            'blog_post_id' => $blogPostId,
            'created_by' => $userId,
            'status' => 'queued',
            'provider' => $config->provider ?? config('content_ops.default_provider', 'local'),
            'model' => $config->model ?? 'local-heuristic-v1',
            'temperature' => $config->temperature ?? 0.3,
            'max_tokens' => $config->max_tokens ?? 2000,
            'input_payload' => $input,
            'prompt_version' => BlogAiAssistantService::PROMPT_VERSION,
        ]);
    }

    public function process(ContentAiJob $job): ContentAiJob
    {
        if (in_array($job->status, ['completed', 'cancelled'], true)) {
            return $job;
        }

        $job->status = 'running';
        $job->started_at = now();
        $job->save();

        $started = microtime(true);

        try {
            $output = match ($job->type) {
                'brief', 'outline', 'draft', 'research' => $this->runAssistantJob($job),
                'seo_audit' => $this->runSeoAudit($job),
                'fact_check' => $this->facts->run($job),
                'internal_linking' => $this->links->run($job),
                'image_suggestion' => $this->runImageSuggestion($job),
                'refresh' => $this->refresh->run($job),
                'repurpose' => $this->repurpose->run($job),
                default => throw new \RuntimeException('Unsupported job type'),
            };

            $text = is_string($output['text'] ?? null) ? $this->sanitizer->sanitizeHtml((string) $output['text']) : null;
            if ($text !== null) {
                $output['text'] = $text;
                $output['manipulation_warnings'] = $this->sanitizer->looksLikeManipulation($text);
            }

            $promptTokens = (int) ($output['prompt_tokens'] ?? 0);
            $completionTokens = (int) ($output['completion_tokens'] ?? 0);
            $cost = $this->costs->estimateCostToman($promptTokens, $completionTokens);

            $job->fill([
                'status' => 'completed',
                'output_payload' => $output,
                'prompt_tokens' => $promptTokens,
                'completion_tokens' => $completionTokens,
                'total_tokens' => $promptTokens + $completionTokens,
                'estimated_cost_toman' => $cost,
                'actual_cost_toman' => $cost,
                'confidence' => $output['confidence'] ?? 0.7,
                'duration_ms' => (int) round((microtime(true) - $started) * 1000),
                'finished_at' => now(),
                'error' => null,
                'provider' => $output['provider'] ?? $job->provider,
                'model' => $output['model'] ?? $job->model,
            ]);
            $job->save();
            $this->costs->logUsage($job->created_by, $job->type, $job);

            return $job->fresh();
        } catch (\Throwable $e) {
            return $this->failOrRetry($job, $e->getMessage(), $started);
        }
    }

    public function processQueued(int $limit = 10): int
    {
        if (! Schema::hasTable('content_ai_jobs')) {
            return 0;
        }
        $jobs = ContentAiJob::query()
            ->where(function ($q) {
                $q->where('status', 'queued')
                    ->orWhere(function ($qq) {
                        $qq->where('status', 'retrying')
                            ->where(function ($q3) {
                                $q3->whereNull('next_retry_at')->orWhere('next_retry_at', '<=', now());
                            });
                    });
            })
            ->orderBy('id')
            ->limit($limit)
            ->get();

        $n = 0;
        foreach ($jobs as $job) {
            $this->process($job);
            $n++;
        }

        return $n;
    }

    /** @return array<string,mixed> */
    private function runAssistantJob(ContentAiJob $job): array
    {
        $input = $job->input_payload ?? [];
        $post = $job->blog_post_id ? BlogPost::find($job->blog_post_id) : null;
        $action = match ($job->type) {
            'outline' => 'outline',
            'draft' => 'intro',
            'research' => 'brief',
            default => 'brief',
        };
        if ($job->type === 'draft') {
            // Prefer fuller draft helper if available
            $action = in_array('draft', $this->assistant->availableActions(), true) ? 'draft' : 'intro';
        }

        $payload = array_merge([
            'title' => $post?->title ?? ($input['title'] ?? ''),
            'content' => $post?->content ?? ($input['content'] ?? ''),
            'focus_keyword' => $post?->focus_keyword ?? ($input['focus_keyword'] ?? ''),
            'search_intent' => $post?->search_intent ?? ($input['search_intent'] ?? 'informational'),
            'category_slug' => $post?->category_slug ?? ($input['category_slug'] ?? ''),
        ], $input);

        $config = Schema::hasTable('content_ai_task_configs')
            ? ContentAiTaskConfig::query()->where('task_key', $job->type)->first()
            : null;
        $system = $config->system_prompt
            ?? 'You are a Persian-first content assistant for Posheh real-estate SaaS. Never invent facts. Never auto-publish.';

        $messages = $this->security->buildMessages($system, json_encode($payload, JSON_UNESCAPED_UNICODE) ?: '', [
            'task' => $job->type,
            'blog_post_id' => $job->blog_post_id,
        ]);

        $provider = $this->registry->get($job->provider);
        $result = $provider->complete([
            'system' => $messages['system'],
            'user' => $messages['user'],
            'task' => $action,
            'temperature' => (float) ($job->temperature ?? 0.3),
            'max_tokens' => (int) ($job->max_tokens ?? 2000),
            'model' => $job->model,
            'timeout' => (int) config('content_ops.job_timeout_sec', 90),
        ]);

        if (! empty($result['error'])) {
            // Graceful fallback to local assistant
            $assist = $this->assistant->assist($action, $payload);
            $result = [
                'text' => json_encode($assist['result'] ?? $assist, JSON_UNESCAPED_UNICODE),
                'prompt_tokens' => 10,
                'completion_tokens' => 40,
                'model' => 'local-heuristic-fallback',
                'raw' => $assist,
                'fallback_from' => $result['error'],
            ];
        }

        return [
            'text' => $result['text'] ?? '',
            'structured' => $result['raw']['result'] ?? $this->sanitizer->validateJson($result['text'] ?? '') ?? $result['raw'] ?? null,
            'prompt_tokens' => (int) ($result['prompt_tokens'] ?? 0),
            'completion_tokens' => (int) ($result['completion_tokens'] ?? 0),
            'model' => $result['model'] ?? $job->model,
            'provider' => $provider->key(),
            'confidence' => isset($result['fallback_from']) ? 0.55 : 0.75,
            'note' => 'AI suggestion only — human must review before publish. No fake facts.',
        ];
    }

    /** @return array<string,mixed> */
    private function runSeoAudit(ContentAiJob $job): array
    {
        $post = BlogPost::findOrFail($job->blog_post_id);
        $payload = $post->toArray();
        $score = $this->quality->score($payload);
        $gate = $this->gate->evaluate($payload, forPublish: false);
        $assist = $this->assistant->assist('cannibalization_check', [
            'title' => $post->title,
            'focus_keyword' => $post->focus_keyword,
            'exclude_id' => $post->id,
        ]);

        return [
            'text' => 'SEO audit complete',
            'structured' => [
                'quality' => $score,
                'gate' => $gate,
                'cannibalization' => $assist['result'] ?? null,
                'note' => 'Internal Quality Guidance — not a Google Score',
            ],
            'prompt_tokens' => 5,
            'completion_tokens' => 20,
            'model' => 'seo-auditor-local',
            'provider' => 'local',
            'confidence' => 0.85,
        ];
    }

    /** @return array<string,mixed> */
    private function runImageSuggestion(ContentAiJob $job): array
    {
        $post = BlogPost::findOrFail($job->blog_post_id);
        $briefs = [
            [
                'purpose' => 'hero',
                'placement' => 'top',
                'subject' => 'تصویر مستند مرتبط با «'.$post->title.'» بدون لوگو جعلی',
                'aspect_ratio' => '1200x630',
                'alt_text' => Str::limit(strip_tags((string) $post->title), 120, ''),
                'caption' => null,
                'prompt' => $post->image_prompt ?: ('Documentary photo related to: '.$post->title.'. No fake logos, no fake charts as facts.'),
            ],
        ];

        if (Schema::hasTable('content_image_briefs')) {
            foreach ($briefs as $b) {
                ContentImageBrief::query()->updateOrCreate(
                    ['blog_post_id' => $post->id, 'purpose' => $b['purpose']],
                    array_merge($b, [
                        'status' => 'suggested',
                        'auto_publish_image' => (bool) config('content_ops.allow_auto_publish_images', false),
                    ])
                );
            }
        }

        return [
            'text' => 'Image briefs suggested — human approval required before publish',
            'structured' => ['briefs' => $briefs],
            'prompt_tokens' => 8,
            'completion_tokens' => 30,
            'model' => 'image-brief-local',
            'provider' => 'local',
            'confidence' => 0.7,
        ];
    }

    private function failOrRetry(ContentAiJob $job, string $error, float $started): ContentAiJob
    {
        $max = (int) config('content_ops.max_retries', 3);
        $job->retry_count = (int) $job->retry_count + 1;
        $job->error = $this->security->redactSecrets($error);
        $job->duration_ms = (int) round((microtime(true) - $started) * 1000);

        if ($job->retry_count <= $max) {
            $delay = (int) min(3600, (2 ** $job->retry_count) * 30);
            $job->status = 'retrying';
            $job->next_retry_at = now()->addSeconds($delay);
        } else {
            $job->status = 'failed';
            $job->finished_at = now();
        }
        $job->save();

        return $job->fresh();
    }
}
