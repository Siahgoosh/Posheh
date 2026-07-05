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
        $request->validate([
            'mobile' => ['required', 'string', 'regex:/^09\d{9}$/'],
        ]);

        $result = $this->otpService->send(new SendOtpDTO(
            mobile: $request->input('mobile'),
        ));

        return response()->json($result);
    }

    public function verifyOtp(Request $request): JsonResponse
    {
        $request->validate([
            'mobile' => ['required', 'string'],
            'code' => ['required', 'string', 'size:6'],
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
        ));

        return response()->json([
            'user' => new UserResource($result['user']),
            'token' => $result['token'],
            'token_type' => $result['token_type'],
            'expires_at' => $result['expires_at'],
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => new UserResource($request->user()->load('office.subscription.plan')),
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
}
