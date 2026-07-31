<?php

namespace App\Services\Auth;

use App\DTOs\Auth\SendOtpDTO;
use App\Models\OtpCode;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\Sms\IpPanelSmsService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class PasswordResetService
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly OtpService $otpService,
    ) {}

    /** @return array<string, mixed> */
    public function forgot(string $channel, string $login): array
    {
        $login = trim($login);

        if ($channel === 'sms') {
            return $this->forgotViaSms($login);
        }

        return $this->forgotViaEmail($login);
    }

    public function reset(array $data): void
    {
        if (($data['channel'] ?? 'email') === 'sms') {
            $this->resetViaSms($data);

            return;
        }

        $this->resetViaEmail($data);
    }

    /** @return array<string, mixed> */
    private function forgotViaEmail(string $login): array
    {
        $user = $this->users->findByLogin($login);

        if (! $user?->email) {
            throw ValidationException::withMessages([
                'login' => ['کاربری با این مشخصات یافت نشد یا ایمیل ثبت نشده است.'],
            ]);
        }

        $plainToken = Str::random(64);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $user->email],
            ['token' => Hash::make($plainToken), 'created_at' => now()]
        );

        $resetUrl = rtrim(config('app.url'), '/').'/reset-password?token='.$plainToken.'&email='.urlencode($user->email);

        try {
            Mail::raw(
                "سلام {$user->name}\n\nبرای بازیابی رمز عبور پوشه روی لینک زیر کلیک کنید:\n{$resetUrl}\n\nاین لینک ۶۰ دقیقه اعتبار دارد.",
                fn ($message) => $message->to($user->email)->subject('بازیابی رمز عبور — پوشه')
            );
        } catch (\Throwable $e) {
            Log::warning('Password reset email failed', ['email' => $user->email, 'error' => $e->getMessage()]);
        }

        $response = [
            'message' => 'اگر حسابی با این مشخصات وجود داشته باشد، لینک بازیابی به ایمیل ارسال شد.',
            'channel' => 'email',
        ];

        if (config('app.debug')) {
            $response['debug_reset_url'] = $resetUrl;
        }

        return $response;
    }

    /** @return array<string, mixed> */
    private function forgotViaSms(string $login): array
    {
        $mobile = $this->normalizeMobile($login);
        $user = $this->users->findByMobile($mobile);

        if (! $user) {
            throw ValidationException::withMessages([
                'login' => ['کاربری با این شماره موبایل یافت نشد.'],
            ]);
        }

        return $this->otpService->send(new SendOtpDTO($mobile, 'password_reset'));
    }

    private function resetViaEmail(array $data): void
    {
        $validated = validator($data, [
            'email' => ['required', 'email'],
            'token' => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ])->validate();

        $record = DB::table('password_reset_tokens')->where('email', $validated['email'])->first();

        if (! $record || ! Hash::check($validated['token'], $record->token)) {
            throw ValidationException::withMessages([
                'token' => ['لینک بازیابی نامعتبر یا منقضی شده است.'],
            ]);
        }

        if ($record->created_at && now()->diffInMinutes($record->created_at) > 60) {
            throw ValidationException::withMessages([
                'token' => ['لینک بازیابی منقضی شده است. دوباره درخواست دهید.'],
            ]);
        }

        $user = User::where('email', $validated['email'])->firstOrFail();
        $user->update(['password' => $validated['password']]);
        DB::table('password_reset_tokens')->where('email', $validated['email'])->delete();
        $user->tokens()->delete();
    }

    private function resetViaSms(array $data): void
    {
        $validated = validator($data, [
            'mobile' => ['required', 'string', 'regex:/^09\d{9}$/'],
            'code' => ['required', 'string', 'size:6'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ])->validate();

        $mobile = $this->normalizeMobile($validated['mobile']);

        $otp = OtpCode::query()
            ->where('mobile', $mobile)
            ->where('purpose', 'password_reset')
            ->whereNull('verified_at')
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if (! $otp || ! hash_equals($otp->code, $validated['code'])) {
            throw ValidationException::withMessages([
                'code' => ['کد تأیید نامعتبر یا منقضی شده است.'],
            ]);
        }

        $user = $this->users->findByMobile($mobile);
        if (! $user) {
            throw ValidationException::withMessages([
                'mobile' => ['کاربر یافت نشد.'],
            ]);
        }

        $otp->update(['verified_at' => now()]);
        $user->update(['password' => $validated['password']]);
        $user->tokens()->delete();
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
}
