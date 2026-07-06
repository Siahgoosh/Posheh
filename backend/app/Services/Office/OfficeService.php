<?php

namespace App\Services\Office;

use App\Enums\UserRole;
use App\Jobs\SendInviteSmsJob;
use App\Models\Office;
use App\Models\OfficeInvitation;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\Activity\ActivityLogger;
use App\Services\Settings\SystemSettingsService;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OfficeService
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly ActivityLogger $activityLogger,
        private readonly SystemSettingsService $settings,
    ) {}

    public function createOffice(array $data, User $manager): Office
    {
        $office = Office::create([
            'name' => $data['name'],
            'phone' => $data['phone'] ?? null,
            'address' => $data['address'] ?? null,
            'city' => $data['city'] ?? null,
            'trial_ends_at' => now()->addDays((int) $this->settings->get('trial_days', 14)),
        ]);

        $manager->update([
            'office_id' => $office->id,
            'role' => UserRole::OfficeManager,
        ]);

        $office->wallet()->create(['balance' => 0]);

        return $office->load('wallet');
    }

    public function inviteConsultant(User $manager, string $mobile, string $role = 'consultant'): OfficeInvitation
    {
        if (! $manager->canManageOffice()) {
            throw ValidationException::withMessages([
                'permission' => ['شما مجاز به دعوت کاربر نیستید.'],
            ]);
        }

        $mobile = $this->normalizeMobile($mobile);

        $existingUser = $this->userRepository->findByMobile($mobile);
        if ($existingUser && $existingUser->office_id === $manager->office_id) {
            throw ValidationException::withMessages([
                'mobile' => ['این کاربر قبلاً عضو دفتر است.'],
            ]);
        }

        $office = Office::with('subscription.plan')->find($manager->office_id);
        $subscription = $office?->subscription?->plan;

        if ($subscription) {
            $currentMembers = User::where('office_id', $manager->office_id)->count();
            if ($currentMembers >= $subscription->max_users) {
                throw ValidationException::withMessages([
                    'mobile' => ['به حداکثر تعداد کاربران پلن رسیده‌اید. اشتراک را ارتقا دهید.'],
                ]);
            }
        }

        $invitation = OfficeInvitation::create([
            'office_id' => $manager->office_id,
            'invited_by' => $manager->id,
            'mobile' => $mobile,
            'role' => $role,
            'token' => Str::random(64),
            'expires_at' => now()->addDays(7),
        ]);

        if (! $existingUser) {
            $this->userRepository->create([
                'name' => 'مشاور',
                'mobile' => $mobile,
                'office_id' => $manager->office_id,
                'role' => UserRole::from($role),
                'is_active' => true,
            ]);
        } elseif (! $existingUser->office_id) {
            $existingUser->update([
                'office_id' => $manager->office_id,
                'role' => UserRole::from($role),
            ]);
        }

        $this->activityLogger->log($manager, 'office.invitation_sent', $invitation, "دعوتنامه برای {$mobile} ارسال شد");

        SendInviteSmsJob::dispatchSync(
            $mobile,
            $office->name ?? 'دفتر املاک',
            $manager->name,
        );

        return $invitation;
    }

    public function getTeamMembers(int $officeId)
    {
        return User::where('office_id', $officeId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    private function normalizeMobile(string $mobile): string
    {
        $mobile = preg_replace('/\D/', '', $mobile);

        if (str_starts_with($mobile, '98')) {
            $mobile = '0'.substr($mobile, 2);
        }

        return $mobile;
    }
}
