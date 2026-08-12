<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use App\Models\Content\BlogImageAudit;
use App\Models\Content\BlogImageJob;
use App\Services\BlogImages\ImageAuditService;
use App\Services\BlogImages\ImageBatchService;
use App\Services\BlogImages\ImageDashboardService;
use App\Services\BlogImages\ImageJobService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BlogImageAdminController extends Controller
{
    public function __construct(
        private readonly ImageDashboardService $dashboard,
        private readonly ImageAuditService $audit,
        private readonly ImageJobService $jobs,
        private readonly ImageBatchService $batches,
    ) {}

    public function dashboard(): JsonResponse
    {
        return response()->json(['data' => $this->dashboard->executive()]);
    }

    public function bootstrap(): JsonResponse
    {
        return response()->json(['data' => $this->jobs->bootstrap()]);
    }

    public function runAudit(): JsonResponse
    {
        return response()->json(['data' => $this->audit->scanAll()]);
    }

    public function audits(Request $request): JsonResponse
    {
        $q = BlogImageAudit::query()->with('post:id,title,slug,is_published,cover_image')->orderByDesc('opportunity_score');
        if ($request->boolean('needs_only')) {
            $q->where('should_generate', true);
        }

        return response()->json(['data' => $q->limit(100)->get()]);
    }

    public function dryRun(Request $request): JsonResponse
    {
        $limit = min(500, max(1, (int) $request->input('limit', 400)));
        $batchSize = min(100, max(1, (int) $request->input('batch_size', config('blog_images.batch_size', 50))));

        return response()->json(['data' => $this->batches->dryRun($limit, $batchSize)]);
    }

    public function confirmBatch(Request $request): JsonResponse
    {
        $data = $request->validate([
            'dry_run' => ['required', 'array'],
            'confirm' => ['required', 'accepted'],
        ]);
        try {
            $batch = $this->batches->createBatchFromDryRun($data['dry_run'], $request->user()?->id, true);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['data' => $batch], 201);
    }

    public function pauseBatch(int $id): JsonResponse
    {
        return response()->json(['data' => $this->batches->pause($id)]);
    }

    public function resumeBatch(int $id): JsonResponse
    {
        return response()->json(['data' => $this->batches->resume($id)]);
    }

    public function cancelBatch(int $id): JsonResponse
    {
        return response()->json(['data' => $this->batches->cancel($id)]);
    }

    public function jobs(Request $request): JsonResponse
    {
        $q = BlogImageJob::query()->orderByDesc('id');
        if ($request->filled('status')) {
            $q->where('status', $request->string('status'));
        }

        return response()->json(['data' => $q->limit(100)->get()]);
    }

    public function enqueue(Request $request): JsonResponse
    {
        $data = $request->validate([
            'blog_post_id' => ['required', 'integer', 'exists:blog_posts,id'],
            'run_now' => ['sometimes', 'boolean'],
        ]);
        try {
            $job = $this->jobs->enqueueForPost(BlogPost::findOrFail($data['blog_post_id']), $request->user()?->id);
            if ($request->boolean('run_now')) {
                $job = $this->jobs->process($job);
            }
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['data' => $job], 201);
    }

    public function process(Request $request): JsonResponse
    {
        $n = $this->jobs->processQueued((int) $request->input('limit', 10));

        return response()->json(['data' => ['processed' => $n]]);
    }

    public function approve(Request $request, int $id): JsonResponse
    {
        try {
            $job = $this->jobs->approve(BlogImageJob::findOrFail($id), $request->user()?->id);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['data' => $job]);
    }

    public function reject(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'reason' => ['required', Rule::in(['Irrelevant', 'Poor Quality', 'Wrong Style', 'Wrong Location', 'Visual Error', 'Duplicate', 'Misleading'])],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);
        $job = $this->jobs->reject(BlogImageJob::findOrFail($id), (int) $request->user()?->id, $data['reason'], $data['note'] ?? null);

        return response()->json(['data' => $job]);
    }

    public function regenerate(Request $request, int $id): JsonResponse
    {
        try {
            $job = $this->jobs->regenerate(BlogImageJob::findOrFail($id), $request->user()?->id);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['data' => $job]);
    }
}
