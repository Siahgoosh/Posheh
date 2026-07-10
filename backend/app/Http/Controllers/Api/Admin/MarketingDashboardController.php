<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\MarketingDashboardService;
use Illuminate\Http\JsonResponse;

class MarketingDashboardController extends Controller
{
    public function __construct(
        private readonly MarketingDashboardService $dashboard,
    ) {}

    public function index(): JsonResponse
    {
        return response()->json(['data' => $this->dashboard->snapshot()]);
    }
}
