<?php

namespace App\Services\Auth;

use App\DTOs\Auth\SendOtpDTO;
use App\DTOs\Auth\VerifyOtpDTO;
use App\Models\Device;
use App\Models\OtpCode;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\Auth\RegistrationService;
use App\Services\Settings\SystemSettingsService;
use App\Jobs\SendOtpSmsJob;
use App\Services\Subscription\SubscriptionAccessService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class OtpService
{
    private const SEND_COOLDOWN_SECONDS = 60;

    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly SystemSettingsService $settings,
        private readonly RegistrationService $registrationService,
        private readonly SubscriptionAccessService $subscriptionAccess,
    ) {}

    public function send(SendOtpDTO $dto): array
    {
        $mobile = $this->normalizeMobile($dto->mobile);

        $rateLimitKey = "otp_rate:{$mobile}";
        $cooldownUntil = Cache::get($rateLimitKey);

        if (is_numeric($cooldownUntil) && now()->timestamp < (int) $cooldownUntil) {
            $remaining = max(1, (int) $cooldownUntil - now()->timestamp);

            throw ValidationException::withMessages([
                'mobile' => ["لطفاً {$remaining} ثانیه صبر کنید و دوباره تلاش کنید."],
            ]);
        }

        if ($cooldownUntil !== null && ! is_numeric($cooldownUntil)) {
            Cache::forget($rateLimitKey);
        }

        $code = $this->generateOtpCode();

        Log::info('OTP saving before SMS dispatch', [
            'mobile' => $this->maskMobile($mobile),
            'code_length' => strlen($code),
            'sms_live' => $this->settings->isSmsLive(),
        ]);

        OtpCode::query()
            ->whereIn('mobile', $this->mobileVariants($mobile))
            ->whereNull('verified_at')
            ->delete();

        OtpCode::create([
            'mobile' => $mobile,
            'code' => $code,
            'purpose' => $dto->purpose,
            'expires_at' => now()->addMinutes(5),
        ]);

        Cache::put($this->otpCacheKey($mobile), $code, now()->addMinutes(5));
        Cache::put(
            $rateLimitKey,
            now()->addSeconds(self::SEND_COOLDOWN_SECONDS)->timestamp,
            now()->addSeconds(self::SEND_COOLDOWN_SECONDS)
        );

        if ($this->settings->isSmsLive()) {
            SendOtpSmsJob::dispatchAfterResponse($mobile, $code);
        } else {
            Log::info("OTP SMS [log] to {$mobile}: {$code}");
        }

        $response = [
            'message' => 'کد تأیید ارسال شد.',
            'expires_in' => 300,
            'sms_sent' => true,
        ];

        if (! $this->settings->isSmsLive()) {
            $response['dev_hint'] = 'حالت تست: کد ۱۲۳۴۵۶';
        }

        return $response;
    }

    private function generateOtpCode(): string
    {
        if (! $this->settings->isSmsLive()) {
            return '123456';
        }

        return (string) random_int(100000, 999999);
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

        $submittedCode = $this->normalizeCode($dto->code);
        $cachedCode = $this->normalizeCode((string) Cache::get($this->otpCacheKey($mobile), ''));

        $activeOtps = $this->activeOtps($mobile);
        $otp = $this->findMatchingOtp($activeOtps, $submittedCode);
        $latestOtp = $activeOtps->first();

        $dbMatches = $otp !== null;
        $cacheMatches = ! $dbMatches
            && $cachedCode !== ''
            && $this->codesMatch($cachedCode, $submittedCode);

        if (! $dbMatches && ! $cacheMatches) {
            RateLimiter::hit($verifyKey, 300);

            if ($latestOtp) {
                if ($latestOtp->attempts >= 5) {
                    throw ValidationException::withMessages([
                        'code' => ['تعداد تلاش‌های ناموفق بیش از حد است. لطفاً کد جدید درخواست دهید.'],
                    ]);
                }

                $latestOtp->increment('attempts');

                Log::warning('OTP mismatch', [
                    'mobile' => $this->maskMobile($mobile),
                    'attempts' => $latestOtp->attempts,
                    'submitted_length' => strlen($submittedCode),
                    'has_cache' => $cachedCode !== '',
                    'active_otp_count' => $activeOtps->count(),
                    'latest_otp_id' => $latestOtp->id,
                ]);

                throw ValidationException::withMessages([
                    'code' => ['کد تأیید اشتباه است. لطفاً آخرین کد دریافتی را وارد کنید.'],
                ]);
            }

            throw ValidationException::withMessages([
                'code' => ['کد تأیید نامعتبر یا منقضی شده است. لطفاً دوباره درخواست کد دهید.'],
            ]);
        }

        if ($otp) {
            $otp->update(['verified_at' => now()]);
        } elseif ($latestOtp && $cacheMatches) {
            $latestOtp->update([
                'code' => $submittedCode,
                'verified_at' => now(),
            ]);
        }

        OtpCode::query()
            ->whereIn('mobile', $this->mobileVariants($mobile))
            ->whereNull('verified_at')
            ->when($otp, fn ($query) => $query->where('id', '!=', $otp->id))
            ->delete();

        Cache::forget($this->otpCacheKey($mobile));
        RateLimiter::clear($verifyKey);

        $user = $this->userRepository->findByMobile($mobile);

        if (! $user) {
            if ($dto->purpose === 'register') {
                $registrationToken = $this->registrationService->createRegistrationToken($mobile);

                return [
                    'needs_registration' => true,
                    'registration_token' => $registrationToken,
                    'mobile' => $mobile,
                ];
            }

            throw ValidationException::withMessages([
                'mobile' => ['کاربری با این شماره موبایل یافت نشد. ابتدا ثبت‌نام کنید.'],
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

        $subscriptionExpired = ! $user->isSuperAdmin()
            && ! $this->subscriptionAccess->userHasAccess($user);

        $token = $user->createToken(
            $dto->deviceName ?? 'web',
            ['*'],
            now()->addDays(30)
        );

        return [
            'user' => $user->load(['office.plan', 'office.subscription.plan']),
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => $token->accessToken->expires_at?->toIso8601String(),
            'subscription_expired' => $subscriptionExpired,
            'access' => $this->subscriptionAccess->accessStatus($user->office),
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

    private function codesMatch(string $stored, string $submitted): bool
    {
        $stored = $this->normalizeCode($stored);
        $submitted = $this->normalizeCode($submitted);

        if ($stored === '' || $submitted === '') {
            return false;
        }

        if (hash_equals($stored, $submitted)) {
            return true;
        }

        if (hash_equals($stored, str_pad($submitted, 6, '0', STR_PAD_LEFT))) {
            return true;
        }

        if (hash_equals(str_pad($stored, 6, '0', STR_PAD_LEFT), str_pad($submitted, 6, '0', STR_PAD_LEFT))) {
            return true;
        }

        return hash_equals(
            ltrim($stored, '0') ?: '0',
            ltrim($submitted, '0') ?: '0'
        );
    }

    /** @return list<string> */
    private function mobileVariants(string $mobile): array
    {
        $normalized = $this->normalizeMobile($mobile);

        return array_values(array_unique(array_filter([
            $mobile,
            $normalized,
            ltrim($normalized, '0'),
            '98'.ltrim($normalized, '0'),
        ])));
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

    private function otpCacheKey(string $mobile): string
    {
        return 'otp_active:'.$mobile;
    }

    /** @return Collection<int, OtpCode> */
    private function activeOtps(string $mobile): Collection
    {
        return OtpCode::query()
            ->whereIn('mobile', $this->mobileVariants($mobile))
            ->whereNull('verified_at')
            ->where('expires_at', '>', now())
            ->orderByDesc('id')
            ->get();
    }

    /** @param Collection<int, OtpCode> $activeOtps */
    private function findMatchingOtp(Collection $activeOtps, string $submittedCode): ?OtpCode
    {
        return $activeOtps->first(
            fn (OtpCode $row) => $this->codesMatch($row->code, $submittedCode)
        );
    }
}
