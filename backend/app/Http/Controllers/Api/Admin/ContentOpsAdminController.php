<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use App\Models\Content\ContentAiJob;
use App\Models\Content\ContentAiCostLimit;
use App\Models\Content\ContentAiTaskConfig;
use App\Models\Content\ContentClaim;
use App\Models\Content\ContentOpsReport;
use App\Models\Content\ContentReviewComment;
use App\Models\Content\ContentStyleProfile;
use App\Services\ContentOps\ContentAiCostControlService;
use App\Services\ContentOps\ContentAiJobService;
use App\Services\ContentOps\ContentAiProviderRegistry;
use App\Services\ContentOps\ContentOpsBootstrapService;
use App\Services\ContentOps\ContentOpsDashboardService;
use App\Services\ContentOps\ContentPipelineService;
use App\Services\ContentOps\EditorialWorkflowService;
use App\Services\ContentOps\PromptSecurityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ContentOpsAdminController extends Controller
{
    public function __construct(
        private readonly ContentOpsDashboardService $dashboard,
        private readonly ContentOpsBootstrapService $bootstrap,
        private readonly ContentAiJobService $jobs,
        private readonly ContentAiCostControlService $costs,
        private readonly ContentAiProviderRegistry $providers,
        private readonly ContentPipelineService $pipeline,
        private readonly EditorialWorkflowService $editorial,
        private readonly PromptSecurityService $security,
    ) {}

    public function dashboard(): JsonResponse
    {
        return response()->json(['data' => $this->dashboard->executive()]);
    }

    public function bootstrap(): JsonResponse
    {
        $result = $this->bootstrap->ensureDefaults();
        $status = ($result['ok'] ?? false) ? 200 : 422;

        return response()->json(['data' => $result], $status);
    }

    public function usage(): JsonResponse
    {
        return response()->json(['data' => $this->costs->usageSummary()]);
    }

    public function providers(): JsonResponse
    {
        return response()->json(['data' => $this->providers->list()]);
    }

    public function jobs(Request $request): JsonResponse
    {
        $q = ContentAiJob::query()->orderByDesc('id');
        if ($request->filled('status')) {
            $q->where('status', $request->string('status'));
        }
        if ($request->filled('type')) {
            $q->where('type', $request->string('type'));
        }

        return response()->json(['data' => $q->limit(100)->get()]);
    }

    public function enqueueJob(Request $request): JsonResponse
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(ContentAiJob::TYPES)],
            'blog_post_id' => ['nullable', 'integer', 'exists:blog_posts,id'],
            'input' => ['nullable', 'array'],
            'idempotency_key' => ['nullable', 'string', 'max:80'],
            'run_now' => ['sometimes', 'boolean'],
        ]);

        try {
            $job = $this->jobs->enqueue(
                $data['type'],
                $data['input'] ?? [],
                $data['blog_post_id'] ?? null,
                $request->user()?->id,
                $data['idempotency_key'] ?? null,
            );
            if ($request->boolean('run_now')) {
                $job = $this->jobs->process($job);
            }
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'AI Assistant Temporarily Unavailable / Job rejected',
                'error' => $this->security->redactSecrets($e->getMessage()),
            ], 422);
        }

        return response()->json(['data' => $job], 201);
    }

    public function processJobs(Request $request): JsonResponse
    {
        $n = $this->jobs->processQueued((int) $request->input('limit', 10));

        return response()->json(['data' => ['processed' => $n]]);
    }

    public function retryJob(int $id): JsonResponse
    {
        $job = ContentAiJob::findOrFail($id);
        if (! in_array($job->status, ['failed', 'retrying'], true)) {
            return response()->json(['message' => 'Job not retryable'], 422);
        }
        $job->status = 'queued';
        $job->next_retry_at = null;
        $job->save();
        $job = $this->jobs->process($job);

        return response()->json(['data' => $job]);
    }

    public function transition(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'to' => ['required', Rule::in(ContentPipelineService::STATUSES)],
        ]);
        $post = BlogPost::findOrFail($id);
        try {
            $post = $this->pipeline->transition($post, $data['to'], $request->user()?->id);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['data' => $post]);
    }

    public function approve(Request $request, int $id): JsonResponse
    {
        $result = $this->editorial->approve(BlogPost::findOrFail($id), (int) $request->user()?->id);

        return response()->json(['data' => $result], ($result['ok'] ?? false) ? 200 : 422);
    }

    public function requestChanges(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'comment' => ['required', 'string', 'max:5000'],
            'section' => ['nullable', 'string', 'max:80'],
        ]);
        $post = $this->editorial->requestChanges(
            BlogPost::findOrFail($id),
            (int) $request->user()?->id,
            $data['comment'],
            $data['section'] ?? null
        );

        return response()->json(['data' => $post]);
    }

    public function publishGate(int $id): JsonResponse
    {
        return response()->json(['data' => $this->editorial->publishGate(BlogPost::findOrFail($id))]);
    }

    public function comments(int $id): JsonResponse
    {
        return response()->json([
            'data' => ContentReviewComment::query()->where('blog_post_id', $id)->orderByDesc('id')->get(),
        ]);
    }

    public function claims(Request $request): JsonResponse
    {
        $q = ContentClaim::query()->orderByDesc('id');
        if ($request->filled('blog_post_id')) {
            $q->where('blog_post_id', $request->integer('blog_post_id'));
        }
        if ($request->boolean('needs_human')) {
            $q->where('requires_human', true)->whereNotIn('status', ['approved', 'rejected']);
        }

        return response()->json(['data' => $q->limit(100)->get()]);
    }

    public function reviewClaim(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['approved', 'rejected', 'needs_source'])],
            'note' => ['nullable', 'string', 'max:2000'],
            'source' => ['nullable', 'string', 'max:255'],
            'source_type' => ['nullable', Rule::in(['official', 'government', 'primary', 'trusted', 'secondary', 'unknown'])],
        ]);
        $claim = ContentClaim::findOrFail($id);
        if (! empty($data['source'])) {
            $claim->source = $data['source'];
        }
        if (! empty($data['source_type'])) {
            $claim->source_type = $data['source_type'];
        }
        $claim = $this->editorial->reviewClaim($claim, (int) $request->user()?->id, $data['status'], $data['note'] ?? null);

        return response()->json(['data' => $claim]);
    }

    public function taskConfigs(): JsonResponse
    {
        return response()->json(['data' => ContentAiTaskConfig::query()->orderBy('task_key')->get()]);
    }

    public function updateTaskConfig(Request $request, int $id): JsonResponse
    {
        $cfg = ContentAiTaskConfig::findOrFail($id);
        $data = $request->validate([
            'provider' => ['nullable', 'string', 'max:40'],
            'model' => ['nullable', 'string', 'max:80'],
            'temperature' => ['nullable', 'numeric', 'min:0', 'max:2'],
            'max_tokens' => ['nullable', 'integer', 'min:100', 'max:16000'],
            'system_prompt' => ['nullable', 'string', 'max:20000'],
            'timeout_sec' => ['nullable', 'integer', 'min:5', 'max:600'],
            'retry_max' => ['nullable', 'integer', 'min:0', 'max:10'],
            'cost_limit_per_job' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
        // Never allow secrets in system prompt storage visibly returning keys
        if (isset($data['system_prompt'])) {
            $data['system_prompt'] = $this->security->redactSecrets($data['system_prompt']);
        }
        $cfg->fill($data)->save();

        return response()->json(['data' => $cfg->fresh()]);
    }

    public function costLimits(): JsonResponse
    {
        return response()->json(['data' => ContentAiCostLimit::query()->orderBy('scope')->get()]);
    }

    public function updateCostLimit(Request $request, int $id): JsonResponse
    {
        $row = ContentAiCostLimit::findOrFail($id);
        $data = $request->validate([
            'limit_toman' => ['nullable', 'integer', 'min:0'],
            'limit_tokens' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
        $row->fill($data)->save();

        return response()->json(['data' => $row->fresh()]);
    }

    public function styles(): JsonResponse
    {
        return response()->json(['data' => ContentStyleProfile::query()->orderByDesc('is_default')->get()]);
    }

    public function reports(Request $request): JsonResponse
    {
        $type = $request->input('type', 'weekly');
        if ($request->boolean('generate')) {
            $payload = $type === 'monthly'
                ? $this->dashboard->buildMonthlyReport()
                : $this->dashboard->buildWeeklyReport();

            return response()->json(['data' => $payload]);
        }

        return response()->json([
            'data' => ContentOpsReport::query()->where('type', $type)->orderByDesc('id')->limit(12)->get(),
        ]);
    }

    public function pipelineStatuses(): JsonResponse
    {
        return response()->json([
            'data' => [
                'statuses' => ContentPipelineService::STATUSES,
                'transitions' => ContentPipelineService::TRANSITIONS,
            ],
        ]);
    }
}
