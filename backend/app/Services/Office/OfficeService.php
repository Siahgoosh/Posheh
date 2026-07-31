<?php

namespace App\Services\Office;

use App\Enums\UserRole;
use App\Models\Office;
use App\Models\OfficeInvitation;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\Activity\ActivityLogger;
use App\Services\Subscription\SubscriptionAccessService;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OfficeService
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly ActivityLogger $activityLogger,
        private readonly SubscriptionAccessService $subscriptionAccess,
    ) {}

    public function createOffice(array $data, User $manager): Office
    {
        $office = Office::create([
            'name' => $data['name'],
            'phone' => $data['phone'] ?? null,
            'address' => $data['address'] ?? null,
            'city' => $data['city'] ?? null,
            'trial_ends_at' => now()->addDays(14),
        ]);

        $manager->update([
            'office_id' => $office->id,
            'role' => UserRole::OfficeManager,
        ]);

        $office->wallet()->create(['balance' => 0]);

        return $office->load('wallet');
    }

    public function inviteConsultant(User $manager, array $data): OfficeInvitation
    {
        if (! $manager->canManageOffice()) {
            throw ValidationException::withMessages([
                'permission' => ['شما مجاز به دعوت کاربر نیستید.'],
            ]);
        }

        $office = $manager->office;
        $plan = $office?->plan ?? $office?->subscription?->plan;
        $maxUsers = $plan?->max_users ?? 3;
        $currentUsers = User::where('office_id', $manager->office_id)->where('is_active', true)->count();

        if ($currentUsers >= $maxUsers) {
            throw ValidationException::withMessages([
                'mobile' => ["حداکثر {$maxUsers} کاربر برای پلن شما مجاز است."],
            ]);
        }

        $mobile = $this->normalizeMobile($data['mobile']);
        $role = $data['role'] ?? 'consultant';

        $existingUser = $this->userRepository->findByMobile($mobile);
        if ($existingUser && $existingUser->office_id === $manager->office_id) {
            throw ValidationException::withMessages([
                'mobile' => ['این کاربر قبلاً عضو دفتر است.'],
            ]);
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
            $this->userRepository->create(array_filter([
                'name' => $data['name'] ?? 'مشاور',
                'mobile' => $mobile,
                'email' => $data['email'] ?? null,
                'username' => isset($data['username']) ? strtolower($data['username']) : null,
                'password' => $data['password'] ?? null,
                'office_id' => $manager->office_id,
                'role' => UserRole::from($role),
                'is_active' => true,
                'mobile_verified_at' => now(),
                'email_verified_at' => ! empty($data['email']) ? now() : null,
            ]));
        } elseif (! $existingUser->office_id) {
            $existingUser->update(array_filter([
                'office_id' => $manager->office_id,
                'role' => UserRole::from($role),
                'email' => $data['email'] ?? $existingUser->email,
                'username' => isset($data['username']) ? strtolower($data['username']) : $existingUser->username,
                'password' => $data['password'] ?? null,
            ]));
        }

        $this->activityLogger->log($manager, 'office.invitation_sent', $invitation, "دعوتنامه برای {$mobile} ارسال شد");

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
