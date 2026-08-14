<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Crm\CrmService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CrmController extends Controller
{
    public function __construct(private readonly CrmService $crm) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->crm->list($request->user())]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        return response()->json(['data' => $this->crm->get($request->user(), $id)]);
    }

    public function pipeline(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->crm->pipelineSummary($request->user())]);
    }

    public function followUps(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->crm->followUps($request->user())]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'contact_name' => ['nullable', 'string'],
            'contact_mobile' => ['nullable', 'string'],
            'stage' => ['nullable', 'string'],
            'value' => ['nullable', 'integer'],
            'property_id' => ['nullable', 'integer'],
            'customer_id' => ['nullable', 'integer'],
            'assigned_to' => ['nullable', 'integer'],
            'notes' => ['nullable', 'string'],
            'priority' => ['nullable', 'string', 'in:low,medium,high,urgent'],
            'source' => ['nullable', 'string', 'max:50'],
            'source_id' => ['nullable', 'integer'],
            'follow_up_at' => ['nullable', 'date'],
            'next_action' => ['nullable', 'string', 'max:255'],
            'lead_score' => ['nullable', 'integer', 'min:0', 'max:100'],
            'campaign_id' => ['nullable', 'integer'],
        ]);

        return response()->json(['data' => $this->crm->create($request->user(), $data)], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'title' => ['sometimes', 'string'],
            'stage' => ['sometimes', 'string'],
            'value' => ['nullable', 'integer'],
            'offer_amount' => ['nullable', 'integer'],
            'notes' => ['nullable', 'string'],
            'assigned_to' => ['nullable', 'integer'],
            'customer_id' => ['nullable', 'integer'],
            'property_id' => ['nullable', 'integer'],
            'priority' => ['nullable', 'string', 'in:low,medium,high,urgent'],
            'lead_score' => ['nullable', 'integer', 'min:0', 'max:100'],
            'follow_up_at' => ['nullable', 'date'],
            'contact_name' => ['nullable', 'string'],
            'contact_mobile' => ['nullable', 'string'],
            'source' => ['nullable', 'string', 'max:50'],
            'source_id' => ['nullable', 'integer'],
            'lost_reason' => ['nullable', 'string', 'max:60'],
            'lost_reason_note' => ['nullable', 'string', 'max:255'],
            'next_action' => ['nullable', 'string', 'max:255'],
            'campaign_id' => ['nullable', 'integer'],
            'deal_status' => ['nullable', 'string', 'max:30'],
        ]);

        return response()->json(['data' => $this->crm->update($request->user(), $id, $data)]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $this->crm->delete($request->user(), $id);

        return response()->json(['message' => 'معامله حذف شد.']);
    }

    public function activities(Request $request, int $id): JsonResponse
    {
        return response()->json(['data' => $this->crm->activities($request->user(), $id)]);
    }

    public function addActivity(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'type' => ['required', 'string', 'in:note,call,visit,email,meeting,sms,whatsapp,telegram,task,follow_up,offer,status_change,stage_change'],
            'body' => ['nullable', 'string'],
            'meta' => ['nullable', 'array'],
        ]);

        return response()->json(['data' => $this->crm->addActivity($request->user(), $id, $data)], 201);
    }
}
