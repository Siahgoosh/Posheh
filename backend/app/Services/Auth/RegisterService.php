<?php

namespace App\Services\Auth;

use App\Enums\UserRole;
use App\Models\Office;
use App\Models\OtpCode;
use App\Models\User;
use App\Models\Wallet;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\Settings\SystemSettingsService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RegisterService
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly SystemSettingsService $settings,
    ) {}

    public function registerManager(string $mobile, string $code, string $name, string $officeName): array
    {
        $mobile = $this->normalizeMobile($mobile);

        if ($this->userRepository->findByMobile($mobile)) {
            throw ValidationException::withMessages([
                'mobile' => ['این شماره قبلاً ثبت شده. وارد شوید.'],
            ]);
        }

        $otp = OtpCode::where('mobile', $mobile)
            ->where('code', $code)
            ->whereNull('verified_at')
            ->latest()
            ->first();

        if (! $otp || ! $otp->isValid()) {
            throw ValidationException::withMessages([
                'code' => ['کد تأیید نامعتبر یا منقضی شده است.'],
            ]);
        }

        return DB::transaction(function () use ($mobile, $name, $officeName, $otp) {
            $otp->update(['verified_at' => now()]);

            $office = Office::create([
                'name' => $officeName,
                'trial_ends_at' => now()->addDays((int) $this->settings->get('trial_days', 14)),
                'is_active' => true,
            ]);

            Wallet::create(['office_id' => $office->id, 'balance' => 0]);

            $user = $this->userRepository->create([
                'name' => $name,
                'mobile' => $mobile,
                'office_id' => $office->id,
                'role' => UserRole::OfficeManager,
                'is_active' => true,
                'mobile_verified_at' => now(),
            ]);

            $token = $user->createToken('web', ['*'], now()->addDays(30));

            return [
                'user' => $user->load('office'),
                'token' => $token->plainTextToken,
                'token_type' => 'Bearer',
                'message' => 'دفتر شما ثبت شد. برای استفاده کامل، پلن اشتراک را خریداری کنید.',
                'requires_subscription' => true,
            ];
        });
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
