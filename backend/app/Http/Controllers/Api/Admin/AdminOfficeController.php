<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Office;
use App\Services\Admin\AuditLogService;
use App\Services\Office\OfficeSiteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminOfficeController extends Controller
{
    public function __construct(
        private readonly OfficeSiteService $siteService,
        private readonly AuditLogService $audit,
    ) {}

    public function show(int $id): JsonResponse
    {
        $office = Office::with(['subscription.plan', 'users', 'wallet'])
            ->withCount(['properties', 'users'])
            ->findOrFail($id);

        return response()->json(['data' => $office]);
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'is_active' => ['sometimes', 'boolean'],
            'plan_active' => ['sometimes', 'boolean'],
            'trial_ends_at' => ['nullable', 'date'],
        ]);

        $office = Office::findOrFail($id);
        $old = $office->only(array_keys($data));
        $office->update($data);
        $this->audit->log('office.status_updated', Office::class, $office->id, 'تغییر وضعیت دفتر', $old, $data);

        return response()->json(['data' => $office->fresh()]);
    }

    public function updatePlanStatus(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'plan_active' => ['required', 'boolean'],
        ]);

        $office = Office::findOrFail($id);
        $office = $this->siteService->adminTogglePlan($office, $data['plan_active']);

        return response()->json([
            'data' => $office,
            'message' => $data['plan_active'] ? 'پلن دفتر فعال شد.' : 'پلن دفتر غیرفعال شد.',
        ]);
    }

    public function updateWebsiteStatus(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'action' => ['required', 'string', 'in:approve,publish,reject,unpublish'],
        ]);

        $office = Office::findOrFail($id);
        $office = $this->siteService->adminApproveWebsite($office, $data['action']);

        return response()->json([
            'data' => $office,
            'message' => 'وضعیت وبسایت به‌روزرسانی شد.',
        ]);
    }
}
