<?php

namespace App\Http\Controllers\Api\Auth;

use App\DTOs\Auth\VerifyOtpDTO;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\Auth\OtpService;
use App\Services\Auth\PasswordAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(
        private readonly OtpService $otpService,
        private readonly PasswordAuthService $passwordAuth,
        private readonly UserRepositoryInterface $users,
    ) {}

    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'login' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
            'device_id' => ['nullable', 'string'],
            'device_name' => ['nullable', 'string'],
            'platform' => ['nullable', 'string'],
        ]);

        $result = $this->passwordAuth->login(
            $request->input('login'),
            $request->input('password'),
            new VerifyOtpDTO(
                mobile: '',
                code: '',
                deviceId: $request->input('device_id'),
                deviceName: $request->input('device_name'),
                platform: $request->input('platform'),
            ),
        );

        return response()->json([
            'user' => new UserResource($result['user']),
            'token' => $result['token'],
            'token_type' => $result['token_type'],
            'expires_at' => $result['expires_at'],
            'subscription_expired' => $result['subscription_expired'] ?? false,
            'access' => $result['access'] ?? null,
        ]);
    }

    public function sendOtp(Request $request): JsonResponse
    {
        $mobile = $this->normalizeMobileInput((string) $request->input('mobile', ''));
        $request->merge(['mobile' => $mobile]);

        $request->validate([
            'mobile' => ['required', 'string', 'regex:/^09\d{9}$/'],
            'purpose' => ['nullable', 'string', 'in:login,register'],
        ]);

        $legacy = $this->users->findByMobile($mobile);
        if ($legacy) {
            throw ValidationException::withMessages([
                'mobile' => [$this->passwordAuth->legacyAccountMessage()],
            ]);
        }

        throw ValidationException::withMessages([
            'mobile' => [
                'ورود و ثبت‌نام با پیامک غیرفعال است. با ایمیل یا نام کاربری و رمز عبور وارد شوید یا ثبت‌نام کنید.',
            ],
        ]);
    }

    public function verifyOtp(Request $request): JsonResponse
    {
        $mobile = $this->normalizeMobileInput((string) $request->input('mobile', ''));
        $request->merge(['mobile' => $mobile]);

        $request->validate([
            'mobile' => ['required', 'string', 'regex:/^09\d{9}$/'],
            'code' => ['required', 'string'],
        ]);

        $legacy = $this->users->findByMobile($mobile);
        if ($legacy) {
            throw ValidationException::withMessages([
                'mobile' => [$this->passwordAuth->legacyAccountMessage()],
            ]);
        }

        throw ValidationException::withMessages([
            'code' => [
                'ورود با پیامک غیرفعال است. از ایمیل/نام کاربری و رمز عبور استفاده کنید.',
            ],
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
