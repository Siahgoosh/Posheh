<?php

namespace App\Services\Subscription;

use App\Models\Office;
use App\Models\User;

class SubscriptionAccessService
{
    public function hasAccess(?Office $office): bool
    {
        if (! $office) {
            return false;
        }

        if (! $office->is_active) {
            return false;
        }

        if ($this->onTrial($office)) {
            return true;
        }

        return $office->hasActiveSubscription();
    }

    public function onTrial(Office $office): bool
    {
        return $office->trial_ends_at !== null && $office->trial_ends_at->isFuture();
    }

    public function trialDaysRemaining(Office $office): int
    {
        if (! $this->onTrial($office)) {
            return 0;
        }

        return max(0, (int) now()->diffInDays($office->trial_ends_at, false));
    }

    public function accessStatus(?Office $office): array
    {
        if (! $office) {
            return [
                'has_access' => false,
                'on_trial' => false,
                'trial_days_remaining' => 0,
                'subscription_expired' => true,
                'reason' => 'no_office',
            ];
        }

        $onTrial = $this->onTrial($office);
        $hasSubscription = $office->hasActiveSubscription();
        $hasAccess = $this->hasAccess($office);

        return [
            'has_access' => $hasAccess,
            'on_trial' => $onTrial,
            'trial_days_remaining' => $this->trialDaysRemaining($office),
            'trial_ends_at' => $office->trial_ends_at?->toIso8601String(),
            'subscription_expired' => ! $hasAccess,
            'has_active_subscription' => $hasSubscription,
            'reason' => $hasAccess
                ? ($onTrial ? 'trial' : 'subscription')
                : ($onTrial ? 'unknown' : 'expired'),
        ];
    }

    public function userHasAccess(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $this->hasAccess($user->office);
    }

    public function officeHasFeature(?Office $office, string $feature): bool
    {
        if (! $office) {
            return false;
        }

        $plan = $office->plan ?? $office->subscription?->plan;

        if (! $plan) {
            return false;
        }

        $features = $plan->features ?? [];

        return in_array($feature, $features, true);
    }
}
