<?php

namespace App\Services\Auth;

use App\DTOs\Auth\SendOtpDTO;
use App\DTOs\Auth\VerifyOtpDTO;
use App\Models\Device;
use App\Models\OtpCode;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\Settings\SystemSettingsService;
use App\Services\Sms\IpPanelSmsService;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class OtpService
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly SystemSettingsService $settings,
        private readonly IpPanelSmsService $sms,
    ) {}

    public function send(SendOtpDTO $dto): array
    {
        $mobile = $this->normalizeMobile($dto->mobile);

        $recentOtp = OtpCode::where('mobile', $mobile)
            ->where('created_at', '>', now()->subMinutes(2))
            ->exists();

        if ($recentOtp) {
            throw ValidationException::withMessages([
                'mobile' => ['لطفاً چند دقیقه صبر کنید و دوباره تلاش کنید.'],
            ]);
        }

        $liveSms = $this->settings->isSmsLive();

        $code = $liveSms
            ? str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT)
            : '123456';

        OtpCode::where('mobile', $mobile)
            ->whereNull('verified_at')
            ->delete();

        OtpCode::create([
            'mobile' => $mobile,
            'code' => $code,
            'purpose' => $dto->purpose,
            'expires_at' => now()->addMinutes(5),
        ]);

        if ($liveSms) {
            $sent = $this->sms->sendOtp($mobile, $code);

            if (! $sent) {
                Log::error('OTP SMS dispatch failed', ['mobile' => $mobile]);

                throw ValidationException::withMessages([
                    'mobile' => ['خطا در ارسال پیامک. لطفاً تنظیمات SMS را بررسی کنید (حالت live و نام کاربری مکث).'],
                ]);
            }
        } else {
            Log::info('OTP generated in log mode (no SMS sent)', [
                'mobile' => $mobile,
                'code' => $code,
                'hint' => 'Set sms_mode to live in admin settings for real OTP delivery',
            ]);
        }

        return [
            'message' => 'کد تأیید ارسال شد.',
            'expires_in' => 300,
        ];
    }

    public function verify(VerifyOtpDTO $dto): array
    {
        $mobile = $this->normalizeMobile($dto->mobile);

        $otp = OtpCode::where('mobile', $mobile)
            ->where('code', $dto->code)
            ->whereNull('verified_at')
            ->latest()
            ->first();

        if (! $otp || ! $otp->isValid()) {
            if ($otp) {
                $otp->increment('attempts');
            }

            throw ValidationException::withMessages([
                'code' => ['کد تأیید نامعتبر یا منقضی شده است.'],
            ]);
        }

        $otp->update(['verified_at' => now()]);

        $user = $this->userRepository->findByMobile($mobile);

        if (! $user) {
            throw ValidationException::withMessages([
                'mobile' => ['کاربری با این شماره موبایل یافت نشد. لطفاً از مدیر دفتر دعوتنامه دریافت کنید.'],
            ]);
        }

        if (! $user->is_active) {
            throw ValidationException::withMessages([
                'mobile' => ['حساب کاربری شما غیرفعال است.'],
            ]);
        }

        $user->update([
            'mobile_verified_at' => now(),
            'last_login_at' => now(),
        ]);

        if ($dto->deviceId) {
            $this->registerDevice($user, $dto);
        }

        $token = $user->createToken(
            $dto->deviceName ?? 'web',
            ['*'],
            now()->addDays(30)
        );

        return [
            'user' => $user->load('office'),
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => $token->accessToken->expires_at?->toIso8601String(),
        ];
    }

    public function logout(User $user, ?string $deviceId = null): void
    {
        if ($deviceId) {
            Device::where('user_id', $user->id)->where('device_id', $deviceId)->delete();
            $user->tokens()->where('name', $deviceId)->delete();
        } else {
            $user->currentAccessToken()?->delete();
        }
    }

    public function logoutAllDevices(User $user): void
    {
        $user->tokens()->delete();
        Device::where('user_id', $user->id)->delete();
    }

    private function registerDevice(User $user, VerifyOtpDTO $dto): void
    {
        Device::updateOrCreate(
            ['device_id' => $dto->deviceId],
            [
                'user_id' => $user->id,
                'device_name' => $dto->deviceName,
                'platform' => $dto->platform,
                'ip_address' => request()->ip(),
                'last_active_at' => now(),
            ]
        );
    }

    private function normalizeMobile(string $mobile): string
    {
        $mobile = preg_replace('/\D/', '', $mobile);

        if (str_starts_with($mobile, '98')) {
            $mobile = '0'.substr($mobile, 2);
        }

        if (! str_starts_with($mobile, '0')) {
            $mobile = '0'.$mobile;
        }

        return $mobile;
    }
}
