<?php

namespace App\Services\Auth;

use App\DTOs\Auth\SendOtpDTO;
use App\DTOs\Auth\VerifyOtpDTO;
use App\Jobs\SendOtpSmsJob;
use App\Models\Device;
use App\Models\OtpCode;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\Settings\SystemSettingsService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class OtpService
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly SystemSettingsService $settings,
    ) {}

    public function send(SendOtpDTO $dto): array
    {
        $mobile = $this->normalizeMobile($dto->mobile);

        $rateLimitKey = "otp_rate:{$mobile}";
        if (Cache::has($rateLimitKey)) {
            throw ValidationException::withMessages([
                'mobile' => ['لطفاً چند دقیقه صبر کنید و دوباره تلاش کنید.'],
            ]);
        }

        $code = $this->generateOtpCode();

        OtpCode::where('mobile', $mobile)
            ->whereNull('verified_at')
            ->delete();

        OtpCode::create([
            'mobile' => $mobile,
            'code' => $code,
            'purpose' => $dto->purpose,
            'expires_at' => now()->addMinutes(5),
        ]);

        Cache::put($rateLimitKey, true, now()->addMinutes(2));

        $smsResult = $this->dispatchOtpSms($mobile, $code);

        return [
            'message' => ($smsResult['success'] ?? false)
                ? 'کد تأیید ارسال شد.'
                : 'کد تأیید ایجاد شد اما ارسال پیامک ناموفق بود. با پشتیبانی تماس بگیرید.',
            'expires_in' => 300,
            'sms_sent' => (bool) ($smsResult['success'] ?? false),
            'sms_debug' => config('app.debug') ? ($smsResult['message'] ?? null) : null,
        ];
    }

    private function generateOtpCode(): string
    {
        if (! $this->settings->isSmsLive() && ! app()->environment('production')) {
            return '123456';
        }

        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    /** @return array{success: bool, message?: string} */
    private function dispatchOtpSms(string $mobile, string $code): array
    {
        try {
            $result = SendOtpSmsJob::dispatchSync($mobile, $code);

            if (is_array($result) && ($result['success'] ?? false)) {
                Log::info('OTP SMS sent', [
                    'mobile' => $this->maskMobile($mobile),
                    'method' => $result['method'] ?? null,
                ]);

                return ['success' => true];
            }

            $message = is_array($result) ? ($result['message'] ?? 'SMS failed') : 'SMS job returned no result';

            Log::error('OTP SMS failed', [
                'mobile' => $this->maskMobile($mobile),
                'message' => $message,
                'sms_mode' => $this->settings->get('sms_mode'),
                'is_live' => $this->settings->isSmsLive(),
            ]);

            return ['success' => false, 'message' => $message];
        } catch (\Throwable $e) {
            Log::error('OTP SMS exception', [
                'mobile' => $this->maskMobile($mobile),
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function maskMobile(string $mobile): string
    {
        return substr($mobile, 0, 4).'***'.substr($mobile, -2);
    }

    public function verify(VerifyOtpDTO $dto): array
    {
        $mobile = $this->normalizeMobile($dto->mobile);
        $verifyKey = 'otp_verify:'.$mobile.':'.request()->ip();

        if (RateLimiter::tooManyAttempts($verifyKey, 10)) {
            throw ValidationException::withMessages([
                'code' => ['تعداد تلاش‌های شما بیش از حد مجاز است. لطفاً چند دقیقه صبر کنید.'],
            ]);
        }

        RateLimiter::hit($verifyKey, 300);

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
        RateLimiter::clear($verifyKey);

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
