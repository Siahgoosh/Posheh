<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\Settings\SystemSettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SystemSettingsAdminController extends Controller
{
    public function __construct(
        private readonly SystemSettingsService $settings,
    ) {}

    public function index(): JsonResponse
    {
        return response()->json([
            'data' => $this->settings->all(maskSecrets: true),
            'zibal' => $this->settings->zibalConfig(),
            'sms' => $this->settings->smsStatus(),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'settings' => ['required', 'array'],
            'settings.*' => ['nullable'],
        ]);

        $result = $this->settings->setMany($data['settings']);

        return response()->json([
            'message' => 'تنظیمات ذخیره شد.',
            'result' => $result,
            'zibal' => $this->settings->zibalConfig(),
        ]);
    }
}
