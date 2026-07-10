<?php

namespace App\Http\Controllers\Api\Auth;

use App\DTOs\Auth\SendOtpDTO;
use App\DTOs\Auth\VerifyOtpDTO;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Services\Auth\OtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(
        private readonly OtpService $otpService,
    ) {}

    public function sendOtp(Request $request): JsonResponse
    {
        $mobile = $this->normalizeMobileInput((string) $request->input('mobile', ''));
        $request->merge(['mobile' => $mobile]);

        $request->validate([
            'mobile' => ['required', 'string', 'regex:/^09\d{9}$/'],
            'purpose' => ['nullable', 'string', 'in:login,register'],
        ]);

        $result = $this->otpService->send(new SendOtpDTO(
            mobile: $request->input('mobile'),
            purpose: $request->input('purpose', 'login'),
        ));

        return response()->json($result);
    }

    public function verifyOtp(Request $request): JsonResponse
    {
        $mobile = $this->normalizeMobileInput((string) $request->input('mobile', ''));
        $code = $this->normalizeCodeInput((string) $request->input('code', ''));

        $request->merge(['mobile' => $mobile, 'code' => $code]);

        $request->validate([
            'mobile' => ['required', 'string', 'regex:/^09\d{9}$/'],
            'code' => ['required', 'string', 'size:6'],
            'purpose' => ['nullable', 'string', 'in:login,register'],
            'device_id' => ['nullable', 'string'],
            'device_name' => ['nullable', 'string'],
            'platform' => ['nullable', 'string'],
        ]);

        $result = $this->otpService->verify(new VerifyOtpDTO(
            mobile: $request->input('mobile'),
            code: $request->input('code'),
            deviceId: $request->input('device_id'),
            deviceName: $request->input('device_name'),
            platform: $request->input('platform'),
            purpose: $request->input('purpose', 'login'),
        ));

        if (! empty($result['needs_registration'])) {
            return response()->json($result);
        }

        return response()->json([
            'user' => new UserResource($result['user']),
            'token' => $result['token'],
            'token_type' => $result['token_type'],
            'expires_at' => $result['expires_at'],
            'subscription_expired' => $result['subscription_expired'] ?? false,
            'access' => $result['access'] ?? null,
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => new UserResource($request->user()->load(['office.plan', 'office.subscription.plan'])),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $this->otpService->logout($request->user(), $request->input('device_id'));

        return response()->json(['message' => 'با موفقیت خارج شدید.']);
    }

    public function logoutAll(Request $request): JsonResponse
    {
        $this->otpService->logoutAllDevices($request->user());

        return response()->json(['message' => 'از همه دستگاه‌ها خارج شدید.']);
    }

    public function devices(Request $request): JsonResponse
    {
        $devices = $request->user()->devices()->latest('last_active_at')->get();

        return response()->json(['data' => $devices]);
    }

    private function normalizeMobileInput(string $mobile): string
    {
        $mobile = preg_replace('/\D/', '', $this->normalizeDigits($mobile));

        if (str_starts_with($mobile, '98')) {
            $mobile = '0'.substr($mobile, 2);
        }

        if ($mobile !== '' && ! str_starts_with($mobile, '0')) {
            $mobile = '0'.$mobile;
        }

        return $mobile;
    }

    private function normalizeCodeInput(string $code): string
    {
        $code = preg_replace('/\D/', '', $this->normalizeDigits(trim($code)));

        if ($code !== '' && strlen($code) < 6) {
            $code = str_pad($code, 6, '0', STR_PAD_LEFT);
        }

        return $code;
    }

    private function normalizeDigits(string $value): string
    {
        $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $arabic = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];

        return str_replace(
            array_merge($persian, $arabic),
            array_merge(range('0', '9'), range('0', '9')),
            $value
        );
    }
}
