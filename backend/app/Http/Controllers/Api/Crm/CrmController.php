<?php

namespace App\Http\Controllers\Api\Crm;

use App\Http\Controllers\Controller;
use App\Services\Crm\CrmService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CrmController extends Controller
{
    public function __construct(private readonly CrmService $service) {}

    public function pipeline(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->service->pipeline($request->user())]);
    }

    public function storeDeal(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'stage_id' => ['nullable', 'integer'],
            'property_id' => ['nullable', 'integer'],
            'customer_name' => ['nullable', 'string'],
            'customer_mobile' => ['nullable', 'string'],
            'value' => ['nullable', 'integer'],
            'notes' => ['nullable', 'string'],
            'next_follow_up_at' => ['nullable', 'date'],
            'assigned_to' => ['nullable', 'integer'],
        ]);

        $deal = $this->service->createDeal($request->user(), $data);

        return response()->json(['data' => $deal->load(['property', 'assignee', 'stage']), 'message' => 'معامله ایجاد شد.'], 201);
    }

    public function moveDeal(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'stage_id' => ['required', 'integer'],
            'sort_order' => ['nullable', 'integer'],
        ]);

        $deal = $this->service->moveDeal($request->user(), $id, $data['stage_id'], $data['sort_order'] ?? 0);

        return response()->json(['data' => $deal]);
    }

    public function updateDeal(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'title' => ['sometimes', 'string'],
            'customer_name' => ['nullable', 'string'],
            'customer_mobile' => ['nullable', 'string'],
            'value' => ['nullable', 'integer'],
            'notes' => ['nullable', 'string'],
            'next_follow_up_at' => ['nullable', 'date'],
            'lead_score' => ['nullable', 'integer'],
        ]);

        $deal = $this->service->updateDeal($request->user(), $id, $data);

        return response()->json(['data' => $deal]);
    }
}
