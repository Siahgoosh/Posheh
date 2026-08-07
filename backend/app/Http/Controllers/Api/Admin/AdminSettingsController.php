<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Communication\Application\Services\CommunicationSettingsService;
use App\Services\Admin\AuditLogService;
use App\Services\Settings\SystemSettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminSettingsController extends Controller
{
    public function __construct(
        private readonly SystemSettingsService $settings,
        private readonly CommunicationSettingsService $communicationSettings,
        private readonly AuditLogService $audit,
    ) {}

    public function index(): JsonResponse
    {
        return response()->json([
            'data' => $this->settings->all(maskSecrets: true),
            'sms' => $this->settings->smsStatus(),
            'zibal' => $this->settings->zibalConfig(),
            'cafe_bazaar' => [
                'package_name' => config('services.cafe_bazaar.package_name'),
                'plan_skus' => config('services.cafe_bazaar.plan_skus'),
                'api_configured' => ! empty(config('services.cafe_bazaar.api_token')),
            ],
            'communication' => $this->communicationSettings->adminStatus(),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'settings' => ['required', 'array'],
        ]);

        $result = $this->settings->setMany($data['settings']);
        $this->audit->log('settings.updated', null, null, 'به‌روزرسانی تنظیمات سیستم', null, [
            'saved' => $result['saved'],
        ]);

        return response()->json([
            'message' => 'تنظیمات ذخیره شد.',
            'result' => $result,
        ]);
    }
}
