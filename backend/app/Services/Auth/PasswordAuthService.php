<?php

namespace App\Services\Auth;

use App\DTOs\Auth\VerifyOtpDTO;
use App\Models\Device;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\Subscription\SubscriptionAccessService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class PasswordAuthService
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly SubscriptionAccessService $subscriptionAccess,
    ) {}

    /** @return array<string, mixed> */
    public function login(string $login, string $password, ?VerifyOtpDTO $device = null): array
    {
        $login = trim($login);
        $verifyKey = 'password_login:'.request()->ip();

        if (RateLimiter::tooManyAttempts($verifyKey, 10)) {
            throw ValidationException::withMessages([
                'login' => ['تعداد تلاش‌های ورود بیش از حد مجاز است. چند دقیقه صبر کنید.'],
            ]);
        }

        if ($this->looksLikeMobile($login)) {
            $legacy = $this->userRepository->findByMobile($this->normalizeMobile($login));
            if ($legacy && empty($legacy->password)) {
                throw ValidationException::withMessages([
                    'login' => [$this->legacyAccountMessage()],
                ]);
            }
        }

        $user = $this->userRepository->findByLogin($login);

        if (! $user || ! $user->password || ! Hash::check($password, $user->password)) {
            RateLimiter::hit($verifyKey, 300);

            throw ValidationException::withMessages([
                'login' => ['ایمیل/نام کاربری یا رمز عبور اشتباه است.'],
            ]);
        }

        if (! $user->is_active) {
            throw ValidationException::withMessages([
                'login' => ['حساب کاربری شما غیرفعال است.'],
            ]);
        }

        RateLimiter::clear($verifyKey);

        $user->update([
            'last_login_at' => now(),
        ]);

        if ($device?->deviceId) {
            $this->registerDevice($user, $device);
        }

        $subscriptionExpired = ! $user->isSuperAdmin()
            && ! $this->subscriptionAccess->userHasAccess($user);

        $token = $user->createToken(
            $device?->deviceName ?? 'web',
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

    public function legacyAccountMessage(): string
    {
        return 'حساب شما قبلاً با شماره موبایل ثبت شده است. برای ورود، ایمیل یا نام کاربری و رمز عبور را وارد کنید. '
            .'اگر هنوز رمز ندارید، از «فراموشی رمز عبور» استفاده کنید یا با پشتیبانی تماس بگیرید.';
    }

    private function looksLikeMobile(string $value): bool
    {
        $digits = preg_replace('/\D/', '', $value);

        return (bool) preg_match('/^09\d{9}$/', $this->normalizeMobile($digits !== '' ? $digits : $value));
    }

    private function normalizeMobile(string $mobile): string
    {
        $mobile = preg_replace('/\D/', '', $mobile);

        if (str_starts_with($mobile, '98')) {
            $mobile = '0'.substr($mobile, 2);
        }

        if ($mobile !== '' && ! str_starts_with($mobile, '0')) {
            $mobile = '0'.$mobile;
        }

        return $mobile;
    }

    private function registerDevice(User $user, VerifyOtpDTO $device): void
    {
        Device::updateOrCreate(
            ['device_id' => $device->deviceId],
            [
                'user_id' => $user->id,
                'device_name' => $device->deviceName,
                'platform' => $device->platform,
                'ip_address' => request()->ip(),
                'last_active_at' => now(),
            ]
        );
    }
}
