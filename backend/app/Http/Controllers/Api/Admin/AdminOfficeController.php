<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Office;
use App\Services\Office\OfficeSiteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminOfficeController extends Controller
{
    public function __construct(private readonly OfficeSiteService $siteService) {}

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
