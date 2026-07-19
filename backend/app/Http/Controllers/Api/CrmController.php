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

    public function pipeline(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->crm->pipelineSummary($request->user())]);
    }

    public function followUps(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->crm->followUps($request->user())]);
    }

    public function stages(): JsonResponse
    {
        return response()->json(['data' => $this->crm->stages()]);
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
            'notes' => ['nullable', 'string'],
            'priority' => ['nullable', 'string', 'in:low,medium,high,urgent'],
            'source' => ['nullable', 'string', 'max:50'],
            'follow_up_at' => ['nullable', 'date'],
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
            'priority' => ['nullable', 'string', 'in:low,medium,high,urgent'],
            'lead_score' => ['nullable', 'integer', 'min:0', 'max:100'],
            'follow_up_at' => ['nullable', 'date'],
            'contact_name' => ['nullable', 'string'],
            'contact_mobile' => ['nullable', 'string'],
        ]);

        return response()->json(['data' => $this->crm->update($request->user(), $id, $data)]);
    }

    public function activities(Request $request, int $id): JsonResponse
    {
        return response()->json(['data' => $this->crm->activities($request->user(), $id)]);
    }

    public function addActivity(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'type' => ['required', 'string', 'in:note,call,visit,email,meeting'],
            'body' => ['required', 'string', 'max:2000'],
        ]);

        return response()->json([
            'data' => $this->crm->addActivity($request->user(), $id, $data),
        ], 201);
    }
}
