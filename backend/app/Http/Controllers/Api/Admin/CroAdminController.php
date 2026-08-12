<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cro\CroCta;
use App\Models\Cro\CroCtaRule;
use App\Models\Cro\CroExperiment;
use App\Models\Cro\CroLead;
use App\Services\Cro\CroBootstrapService;
use App\Services\Cro\CroFunnelDashboardService;
use App\Services\Cro\CroLeadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CroAdminController extends Controller
{
    public function __construct(
        private readonly CroFunnelDashboardService $dashboard,
        private readonly CroLeadService $leads,
        private readonly CroBootstrapService $bootstrap,
    ) {}

    public function dashboard(Request $request): JsonResponse
    {
        $days = max(7, min(90, (int) $request->input('days', 28)));

        return response()->json(['data' => $this->dashboard->executive($days)]);
    }

    public function bootstrap(): JsonResponse
    {
        $this->bootstrap->ensureDefaults();

        return response()->json(['message' => 'OK']);
    }

    public function leads(Request $request): JsonResponse
    {
        $q = CroLead::query()->latest();
        if ($status = $request->string('status')->toString()) {
            $q->where('status', $status);
        }
        if ($source = $request->string('source')->toString()) {
            $q->where('source', $source);
        }

        return response()->json(['data' => $q->limit(100)->get()]);
    }

    public function updateLeadStatus(Request $request, int $id): JsonResponse
    {
        $lead = CroLead::query()->findOrFail($id);
        $status = $request->validate(['status' => ['required', 'string']])['status'];

        return response()->json(['data' => $this->leads->updateStatus($lead, $status, $request->user())]);
    }

    public function feedback(Request $request, int $id): JsonResponse
    {
        $lead = CroLead::query()->findOrFail($id);
        $feedback = $request->validate(['quality_feedback' => ['required', 'string']])['quality_feedback'];

        return response()->json(['data' => $this->leads->setQualityFeedback($lead, $feedback)]);
    }

    public function ctas(): JsonResponse
    {
        return response()->json(['data' => CroCta::query()->orderBy('priority')->with('rules')->get()]);
    }

    public function storeCta(Request $request): JsonResponse
    {
        $data = $request->validate([
            'key' => ['required', 'string', 'max:100', 'unique:cro_ctas,key'],
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:500'],
            'button_text' => ['required', 'string', 'max:80'],
            'url' => ['required', 'string', 'max:500'],
            'image' => ['nullable', 'string', 'max:500'],
            'type' => ['nullable', 'string', 'max:40'],
            'category' => ['nullable', 'string', 'max:100'],
            'topic' => ['nullable', 'string', 'max:100'],
            'funnel_stage' => ['nullable', 'string', 'max:20'],
            'intent' => ['nullable', 'string', 'max:40'],
            'priority' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $cta = CroCta::query()->create($data + ['is_active' => $data['is_active'] ?? true, 'priority' => $data['priority'] ?? 100]);

        return response()->json(['data' => $cta], 201);
    }

    public function updateCta(Request $request, int $id): JsonResponse
    {
        $cta = CroCta::query()->findOrFail($id);
        $data = $request->validate([
            'title' => ['sometimes', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:500'],
            'button_text' => ['sometimes', 'string', 'max:80'],
            'url' => ['sometimes', 'string', 'max:500'],
            'image' => ['nullable', 'string', 'max:500'],
            'type' => ['nullable', 'string', 'max:40'],
            'category' => ['nullable', 'string', 'max:100'],
            'topic' => ['nullable', 'string', 'max:100'],
            'funnel_stage' => ['nullable', 'string', 'max:20'],
            'intent' => ['nullable', 'string', 'max:40'],
            'priority' => ['nullable', 'integer'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $cta->update($data);

        return response()->json(['data' => $cta->fresh()]);
    }

    public function storeRule(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'cro_cta_id' => ['required', 'integer', 'exists:cro_ctas,id'],
            'match_field' => ['required', 'string', 'max:40'],
            'match_operator' => ['nullable', 'string', 'max:20'],
            'match_value' => ['required', 'string', 'max:200'],
            'priority' => ['nullable', 'integer'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $rule = CroCtaRule::query()->create($data + [
            'match_operator' => $data['match_operator'] ?? 'eq',
            'priority' => $data['priority'] ?? 100,
            'is_active' => $data['is_active'] ?? true,
        ]);

        return response()->json(['data' => $rule], 201);
    }

    public function experiments(): JsonResponse
    {
        return response()->json(['data' => CroExperiment::query()->latest()->limit(50)->get()]);
    }

    public function storeExperiment(Request $request): JsonResponse
    {
        $minSample = (int) config('cro.ab_test.min_sample_per_variant', 200);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'hypothesis' => ['nullable', 'string', 'max:1000'],
            'target_type' => ['nullable', 'string', 'max:40'],
            'variant_a' => ['required', 'string', 'max:500'],
            'variant_b' => ['nullable', 'string', 'max:500'],
            'metric' => ['nullable', 'string', 'max:40'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'status' => ['nullable', 'string', 'max:40'],
        ]);
        if (($data['status'] ?? 'draft') === 'running') {
            // Do not auto-declare winners; just store with caution note
            $data['decision'] = $data['decision'] ?? "Do not declare winner before {$minSample} samples/variant and min duration.";
        }
        $exp = CroExperiment::query()->create($data + [
            'target_type' => $data['target_type'] ?? 'cta',
            'metric' => $data['metric'] ?? 'cta_click',
            'status' => $data['status'] ?? 'draft',
        ]);

        return response()->json(['data' => $exp], 201);
    }
}
