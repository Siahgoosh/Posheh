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
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
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

        $rateLimitKey = "otp_rate:{$mobile}";
        if (Cache::has($rateLimitKey)) {
            throw ValidationException::withMessages([
                'mobile' => ['لطفاً چند دقیقه صبر کنید و دوباره تلاش کنید.'],
            ]);
        }

        $code = $this->generateOtpCode();

        Log::info('OTP dispatching', [
            'mobile' => $this->maskMobile($mobile),
            'code_length' => strlen($code),
        ]);

        $smsResult = $this->dispatchOtpSms($mobile, $code);

        if (! ($smsResult['success'] ?? false)) {
            Log::error('OTP SMS failed — code not saved', [
                'mobile' => $this->maskMobile($mobile),
                'message' => $smsResult['message'] ?? null,
            ]);

            $userMessage = $this->sanitizeSmsError($smsResult['message'] ?? null);

            throw ValidationException::withMessages([
                'mobile' => [$userMessage],
            ]);
        }

        OtpCode::where('mobile', $mobile)
            ->whereNull('verified_at')
            ->delete();

        OtpCode::create([
            'mobile' => $mobile,
            'code' => $code,
            'purpose' => $dto->purpose,
            'expires_at' => now()->addMinutes(5),
        ]);

        Cache::put($this->otpCacheKey($mobile), $code, now()->addMinutes(5));
        Cache::put($rateLimitKey, true, now()->addMinutes(2));

        Log::info('OTP saved after SMS', [
            'mobile' => $this->maskMobile($mobile),
            'method' => $smsResult['method'] ?? null,
        ]);

        return [
            'message' => 'کد تأیید ارسال شد.',
            'expires_in' => 300,
            'sms_sent' => true,
            'sms_debug' => config('app.debug') ? ($smsResult['method'] ?? null) : null,
        ];
    }

    private function generateOtpCode(): string
    {
        if (! $this->settings->isSmsLive() && ! app()->environment('production')) {
            return '123456';
        }

        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    /** @return array{success: bool, message?: string, method?: string} */
    private function dispatchOtpSms(string $mobile, string $code): array
    {
        try {
            $result = $this->sms->sendOtp($mobile, $code);

            if (($result['success'] ?? false)) {
                Log::info('OTP SMS sent', [
                    'mobile' => $this->maskMobile($mobile),
                    'method' => $result['method'] ?? null,
                ]);

                return [
                    'success' => true,
                    'method' => $result['method'] ?? null,
                ];
            }

            $message = $result['message'] ?? 'SMS failed';

            Log::error('OTP SMS failed', [
                'mobile' => $this->maskMobile($mobile),
                'message' => $message,
                'method' => $result['method'] ?? null,
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

        $submittedCode = $this->normalizeCode($dto->code);
        $cachedCode = $this->normalizeCode((string) Cache::get($this->otpCacheKey($mobile), ''));

        $otp = OtpCode::where('mobile', $mobile)
            ->where('code', $submittedCode)
            ->whereNull('verified_at')
            ->where('expires_at', '>', now())
            ->where('attempts', '<', 5)
            ->latest()
            ->first();

        if (! $otp && $cachedCode !== '' && hash_equals($cachedCode, $submittedCode)) {
            $otp = OtpCode::where('mobile', $mobile)
                ->whereNull('verified_at')
                ->where('expires_at', '>', now())
                ->where('attempts', '<', 5)
                ->latest()
                ->first();

            if ($otp && ! hash_equals($this->normalizeCode($otp->code), $submittedCode)) {
                Log::warning('OTP cache/db drift corrected', [
                    'mobile' => $this->maskMobile($mobile),
                ]);
                $otp->update(['code' => $submittedCode]);
            }
        }

        if (! $otp) {
            $activeOtp = OtpCode::where('mobile', $mobile)
                ->whereNull('verified_at')
                ->where('expires_at', '>', now())
                ->where('attempts', '<', 5)
                ->latest()
                ->first();

            if ($activeOtp) {
                $activeOtp->increment('attempts');

                Log::warning('OTP mismatch', [
                    'mobile' => $this->maskMobile($mobile),
                    'attempts' => $activeOtp->attempts,
                    'submitted_length' => strlen($submittedCode),
                ]);

                throw ValidationException::withMessages([
                    'code' => ['کد تأیید اشتباه است. لطفاً آخرین کد دریافتی را وارد کنید.'],
                ]);
            }

            throw ValidationException::withMessages([
                'code' => ['کد تأیید نامعتبر یا منقضی شده است. لطفاً دوباره درخواست کد دهید.'],
            ]);
        }

        $otp->update(['verified_at' => now()]);
        Cache::forget($this->otpCacheKey($mobile));
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

    private function normalizeCode(string $code): string
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

    private function normalizeMobile(string $mobile): string
    {
        $mobile = $this->normalizeDigits($mobile);
        $mobile = preg_replace('/\D/', '', $mobile);

        if (str_starts_with($mobile, '98')) {
            $mobile = '0'.substr($mobile, 2);
        }

        if (! str_starts_with($mobile, '0')) {
            $mobile = '0'.$mobile;
        }

        return $mobile;
    }

    private function sanitizeSmsError(?string $message): string
    {
        if (config('app.debug') && $message !== null && trim($message) !== '') {
            $message = preg_replace('/https?:\/\/\S+/', '[sms-api]', $message) ?? $message;
            $message = preg_replace('/password=\S+/i', 'password=***', $message) ?? $message;

            return mb_strlen($message) > 180
                ? 'ارسال پیامک ناموفق بود. لطفاً چند دقیقه بعد دوباره تلاش کنید.'
                : $message;
        }

        return 'ارسال پیامک ناموفق بود. لطفاً چند دقیقه بعد دوباره تلاش کنید.';
    }

    private function otpCacheKey(string $mobile): string
    {
        return 'otp_active:'.$mobile;
    }
}
