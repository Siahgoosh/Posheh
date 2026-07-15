<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\MarketingDashboardService;
use App\Services\Settings\SystemSettingsService;
use Illuminate\Http\JsonResponse;

class MarketingDashboardController extends Controller
{
    public function __construct(
        private readonly MarketingDashboardService $dashboard,
        private readonly SystemSettingsService $settings,
    ) {}

    public function index(): JsonResponse
    {
        return response()->json([
            'data' => array_merge($this->dashboard->snapshot(), [
                'system' => [
                    'sms' => $this->settings->smsStatus(),
                    'app_env' => app()->environment(),
                    'app_debug' => (bool) config('app.debug'),
                ],
            ]),
        ]);
    }

    public function smsStatus(): JsonResponse
    {
        return response()->json(['data' => $this->settings->smsStatus()]);
    }
}
