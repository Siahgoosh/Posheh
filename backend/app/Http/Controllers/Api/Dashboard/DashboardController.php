<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Resources\ActivityResource;
use App\Http\Resources\PropertyResource;
use App\Http\Resources\TaskResource;
use App\Services\Dashboard\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardService $dashboardService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'stats' => $this->dashboardService->getStats($user),
            'recent_properties' => PropertyResource::collection(
                $this->dashboardService->getRecentProperties($user)
            ),
            'expiring_properties' => PropertyResource::collection(
                $this->dashboardService->getExpiringProperties($user)
            ),
            'activities' => ActivityResource::collection(
                $this->dashboardService->getRecentActivities($user)
            ),
            'tasks' => TaskResource::collection(
                $this->dashboardService->getTasks($user)
            ),
        ]);
    }
}
