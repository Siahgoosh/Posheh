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
        ]);

        return response()->json(['data' => $this->crm->create($request->user(), $data)], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'title' => ['sometimes', 'string'],
            'stage' => ['sometimes', 'string'],
            'value' => ['nullable', 'integer'],
            'notes' => ['nullable', 'string'],
            'assigned_to' => ['nullable', 'integer'],
        ]);

        return response()->json(['data' => $this->crm->update($request->user(), $id, $data)]);
    }
}
