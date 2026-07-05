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
            'sms_status' => $this->settings->smsStatus(),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $request->validate([
            'settings' => ['required', 'array'],
        ]);

        $result = $this->settings->setMany($request->input('settings'));

        if (! empty($result['errors'])) {
            return response()->json([
                'message' => 'برخی تنظیمات ذخیره نشدند.',
                'saved' => $result['saved'],
                'skipped' => $result['skipped'],
                'errors' => $result['errors'],
                'data' => $this->settings->all(maskSecrets: true),
                'sms_status' => $this->settings->smsStatus(),
            ], 422);
        }

        return response()->json([
            'message' => 'تنظیمات با موفقیت ذخیره شد.',
            'saved' => $result['saved'],
            'skipped' => $result['skipped'],
            'data' => $this->settings->all(maskSecrets: true),
            'sms_status' => $this->settings->smsStatus(),
        ]);
    }

    public function smsStatus(): JsonResponse
    {
        return response()->json([
            'data' => $this->settings->smsStatus(),
        ]);
    }

    public function testSms(Request $request): JsonResponse
    {
        $request->validate([
            'mobile' => ['required', 'string', 'regex:/^09\d{9}$/'],
            'message' => ['nullable', 'string', 'max:500'],
            'settings' => ['nullable', 'array'],
        ]);

        if ($request->filled('settings')) {
            $this->settings->setMany($request->input('settings'));
        }

        $result = $this->sms->test(
            $request->input('mobile'),
            $request->input('message', 'تست پیامک پوشه - مدیر سیستم'),
            $request->filled('settings')
                ? $this->settings->ippanelConfigFromArray($request->input('settings'))
                : null
        );

        return response()->json([
            ...$result,
            'sms_status' => $this->settings->smsStatus(),
        ], $result['success'] ? 200 : 422);
    }
}
