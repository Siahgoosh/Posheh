<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlatformLead;
use App\Services\Admin\PlatformLeadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminPlatformLeadController extends Controller
{
    public function __construct(private readonly PlatformLeadService $service) {}

    public function index(Request $request): JsonResponse
    {
        $payload = $this->service->inbox(
            $request->input('stage'),
            $request->input('source'),
        );

        return response()->json($payload);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:120'],
            'mobile' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:120'],
            'message' => ['nullable', 'string', 'max:2000'],
            'office_id' => ['nullable', 'integer', 'exists:offices,id'],
            'property_id' => ['nullable', 'integer', 'exists:properties,id'],
            'stage' => ['nullable', 'string', 'in:new,contacted,qualified,demo,won,lost'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'follow_up_at' => ['nullable', 'date'],
        ]);

        $lead = $this->service->create($validated);

        return response()->json(['data' => $lead, 'message' => 'سرنخ ثبت شد.'], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $lead = PlatformLead::findOrFail($id);
        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:120'],
            'mobile' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:120'],
            'message' => ['nullable', 'string', 'max:2000'],
            'stage' => ['nullable', 'string', 'in:new,contacted,qualified,demo,won,lost'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'follow_up_at' => ['nullable', 'date'],
        ]);

        $lead = $this->service->update($lead, $validated);

        return response()->json(['data' => $lead, 'message' => 'سرنخ به‌روز شد.']);
    }

    public function stages(): JsonResponse
    {
        return response()->json(['data' => PlatformLead::STAGES]);
    }
}
