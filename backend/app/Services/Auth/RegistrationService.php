<?php

namespace App\Services\Auth;

use App\DTOs\Auth\VerifyOtpDTO;
use App\Enums\PanelType;
use App\Enums\UserRole;
use App\Models\Office;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\Settings\SystemSettingsService;
use App\Services\Subscription\SubscriptionAccessService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RegistrationService
{
    public function __construct(
        private readonly SystemSettingsService $settings,
        private readonly SubscriptionAccessService $accessService,
    ) {}

    public function createRegistrationToken(string $mobile): string
    {
        $token = Str::random(64);
        Cache::put($this->tokenKey($token), $this->normalizeMobile($mobile), now()->addMinutes(20));

        return $token;
    }

    public function resolveMobileFromToken(string $token): ?string
    {
        return Cache::get($this->tokenKey($token));
    }

    /** @return array<string, mixed> */
    public function register(string $registrationToken, array $data, ?VerifyOtpDTO $device = null): array
    {
        $mobile = $this->resolveMobileFromToken($registrationToken);

        if (! $mobile) {
            throw ValidationException::withMessages([
                'registration_token' => ['نشست ثبت‌نام منقضی شده. دوباره کد تأیید بگیرید.'],
            ]);
        }

        if (User::where('mobile', $mobile)->exists()) {
            throw ValidationException::withMessages([
                'mobile' => ['این شماره قبلاً ثبت شده است. وارد شوید.'],
            ]);
        }

        $plan = SubscriptionPlan::where('slug', $data['plan_slug'])
            ->where('is_active', true)
            ->firstOrFail();

        $panelType = PanelType::from($plan->panel_type);
        $soloTrialDays = (int) $this->settings->get('trial_days_solo', 3);

        return DB::transaction(function () use ($mobile, $data, $plan, $panelType, $device, $registrationToken, $soloTrialDays) {
            $officeData = $this->buildOfficeData($data, $plan, $panelType, $soloTrialDays);
            $office = Office::create($officeData);

            if (! empty($data['logo']) && $data['logo'] instanceof \Illuminate\Http\UploadedFile) {
                $path = $data['logo']->store('offices/logos', 'public');
                $office->update(['logo_path' => $path]);
            }

            $office->wallet()->create(['balance' => 0]);

            $userName = $panelType->isSolo()
                ? trim(($data['first_name'] ?? '').' '.($data['last_name'] ?? ''))
                : ($data['manager_name'] ?? $office->name);

            $user = User::create([
                'name' => $userName,
                'mobile' => $mobile,
                'office_id' => $office->id,
                'role' => UserRole::OfficeManager,
                'is_active' => true,
                'mobile_verified_at' => now(),
                'last_login_at' => now(),
            ]);

            Cache::forget($this->tokenKey($registrationToken));

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
                'access' => $this->accessService->accessStatus($office->fresh()),
            ];
        });
    }

    /** @return array<string, mixed> */
    private function buildOfficeData(array $data, SubscriptionPlan $plan, PanelType $panelType, int $soloTrialDays): array
    {
        $trialEndsAt = $panelType->isSolo() ? now()->addDays($soloTrialDays) : null;

        if ($panelType->isSolo()) {
            $name = trim(($data['first_name'] ?? '').' '.($data['last_name'] ?? ''));

            return [
                'name' => $name,
                'phone' => $this->normalizeMobile($data['mobile'] ?? ''),
                'subscription_plan_id' => $plan->id,
                'panel_type' => $panelType->value,
                'is_active' => true,
                'trial_ends_at' => $trialEndsAt,
                'show_on_website' => false,
                'is_verified' => false,
            ];
        }

        return [
            'name' => $data['office_name'],
            'phone' => $data['office_phone'] ?? null,
            'address' => $data['office_address'] ?? null,
            'city' => $data['office_city'] ?? null,
            'description' => $data['office_description'] ?? null,
            'subscription_plan_id' => $plan->id,
            'panel_type' => $panelType->value,
            'is_active' => true,
            'trial_ends_at' => $trialEndsAt,
            'show_on_website' => $panelType === PanelType::Premium,
            'is_verified' => $panelType === PanelType::Premium,
            'telegram_bot_token' => $data['telegram_bot_token'] ?? null,
            'whatsapp_config' => ! empty($data['whatsapp_phone'])
                ? ['phone' => $data['whatsapp_phone'], 'enabled' => true]
                : null,
        ];
    }

    private function tokenKey(string $token): string
    {
        return 'registration:'.$token;
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
