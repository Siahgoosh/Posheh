<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CrmLostReason;
use App\Models\CrmPipelineStage;
use App\Models\CrmScoreRule;
use App\Models\CrmSource;
use App\Models\CrmTag;
use App\Services\Crm\CrmBootstrapService;
use App\Services\Crm\CrmService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CrmMetaController extends Controller
{
    public function __construct(
        private readonly CrmBootstrapService $bootstrap,
        private readonly CrmService $crm,
    ) {}

    public function bootstrap(Request $request): JsonResponse
    {
        $this->bootstrap->ensureForUser($request->user());

        return response()->json(['message' => 'پیکربندی پیش‌فرض CRM آماده است.']);
    }

    public function stages(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->crm->stages($request->user())]);
    }

    public function sources(Request $request): JsonResponse
    {
        $this->bootstrap->ensureForUser($request->user());
        $items = CrmSource::where('office_id', $request->user()->office_id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return response()->json(['data' => $items]);
    }

    public function lostReasons(Request $request): JsonResponse
    {
        $this->bootstrap->ensureForUser($request->user());
        $items = CrmLostReason::where('office_id', $request->user()->office_id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return response()->json(['data' => $items]);
    }

    public function tags(Request $request): JsonResponse
    {
        $this->bootstrap->ensureForUser($request->user());
        $items = CrmTag::where('office_id', $request->user()->office_id)->orderBy('name')->get();

        return response()->json(['data' => $items]);
    }

    public function storeTag(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user->canManageOffice()) {
            abort(403);
        }
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'color' => ['nullable', 'string', 'max:30'],
        ]);
        $tag = CrmTag::create([
            'office_id' => $user->office_id,
            'name' => $data['name'],
            'color' => $data['color'] ?? null,
        ]);

        return response()->json(['data' => $tag], 201);
    }

    public function scoreRules(Request $request): JsonResponse
    {
        $this->bootstrap->ensureForUser($request->user());
        $items = CrmScoreRule::where('office_id', $request->user()->office_id)
            ->where('is_active', true)
            ->orderBy('id')
            ->get();

        return response()->json(['data' => $items]);
    }

    public function duplicateCheck(Request $request): JsonResponse
    {
        $data = $request->validate([
            'mobile' => ['required', 'string', 'max:20'],
            'exclude_id' => ['nullable', 'integer'],
        ]);

        return response()->json([
            'data' => $this->crm->findDuplicateCustomers(
                $request->user(),
                $data['mobile'],
                $data['exclude_id'] ?? null,
            ),
        ]);
    }

    public function updateStage(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        if (! $user->canManageOffice()) {
            abort(403);
        }
        $stage = CrmPipelineStage::where('office_id', $user->office_id)->findOrFail($id);
        if ($stage->is_system) {
            $data = $request->validate([
                'label' => ['sometimes', 'string', 'max:80'],
                'color' => ['nullable', 'string', 'max:30'],
                'sort_order' => ['sometimes', 'integer', 'min:0', 'max:1000'],
                'is_active' => ['sometimes', 'boolean'],
            ]);
        } else {
            $data = $request->validate([
                'label' => ['sometimes', 'string', 'max:80'],
                'color' => ['nullable', 'string', 'max:30'],
                'sort_order' => ['sometimes', 'integer', 'min:0', 'max:1000'],
                'is_active' => ['sometimes', 'boolean'],
                'is_won' => ['sometimes', 'boolean'],
                'is_lost' => ['sometimes', 'boolean'],
            ]);
        }
        $stage->update($data);

        return response()->json(['data' => $stage->fresh()]);
    }
}
