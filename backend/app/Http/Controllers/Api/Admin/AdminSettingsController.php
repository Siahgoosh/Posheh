<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\Settings\SystemSettingsService;
use App\Services\Sms\IpPanelSmsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminSettingsController extends Controller
{
    public function __construct(
        private readonly SystemSettingsService $settings,
        private readonly IpPanelSmsService $sms,
    ) {}

    public function index(): JsonResponse
    {
        return response()->json([
            'data' => $this->settings->all(maskSecrets: true),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $request->validate([
            'settings' => ['required', 'array'],
        ]);

        $this->settings->setMany($request->input('settings'));

        return response()->json([
            'message' => 'تنظیمات با موفقیت ذخیره شد.',
            'data' => $this->settings->all(maskSecrets: true),
        ]);
    }

    public function testSms(Request $request): JsonResponse
    {
        $request->validate([
            'mobile' => ['required', 'string', 'regex:/^09\d{9}$/'],
            'message' => ['nullable', 'string', 'max:500'],
        ]);

        $result = $this->sms->test(
            $request->input('mobile'),
            $request->input('message', 'تست پیامک پوشه - مدیر سیستم')
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }
}
