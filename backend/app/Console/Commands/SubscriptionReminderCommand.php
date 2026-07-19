<?php

namespace App\Console\Commands;

use App\Models\Office;
use App\Models\Subscription;
use App\Services\Sms\IpPanelSmsService;
use App\Services\Subscription\SubscriptionAccessService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class SubscriptionReminderCommand extends Command
{
    protected $signature = 'subscriptions:remind';

    protected $description = 'Send SMS reminders 2 days before trial/subscription expiry';

    public function handle(SubscriptionAccessService $access, IpPanelSmsService $sms): int
    {
        $offices = Office::with('users')->where('is_active', true)->get();
        $sent = 0;

        foreach ($offices as $office) {
            if ($office->onTrial()) {
                $daysLeft = $access->trialDaysRemaining($office);
                if ($daysLeft === 2 && $this->markSent("trial:{$office->id}:2")) {
                    $this->notifyOffice(
                        $office,
                        'پوشه: دوره آزمایشی شما ۲ روز دیگر تمام می‌شود. برای تمدید از دکمه «تمدید اشتراک» در پنل یا posheapp.ir/renew استفاده کنید.',
                        $sms,
                    );
                    $sent++;
                }
                continue;
            }

            $activeSub = Subscription::where('office_id', $office->id)
                ->where('status', 'active')
                ->where('ends_at', '>', now())
                ->latest('ends_at')
                ->first();

            if ($activeSub && (int) now()->diffInDays($activeSub->ends_at, false) === 2) {
                if ($this->markSent("sub:{$office->id}:{$activeSub->id}:2")) {
                    $this->notifyOffice(
                        $office,
                        'پوشه: اشتراک شما ۲ روز دیگر تمام می‌شود. همین حالا از posheapp.ir/renew تمدید کنید.',
                        $sms,
                    );
                    $sent++;
                }
                continue;
            }

            if (! $access->hasAccess($office)) {
                $cacheKey = "sub_expired:{$office->id}:".now()->toDateString();
                if (! Cache::has($cacheKey)) {
                    $this->notifyOffice($office, 'اشتراک پوشه منقضی شده. ورود فقط پس از تمدید از posheapp.ir/renew ممکن است.', $sms);
                    Cache::put($cacheKey, true, now()->addDay());
                    $sent++;
                }
            }
        }

        $this->info("Sent {$sent} reminders.");

        return self::SUCCESS;
    }

    private function markSent(string $key): bool
    {
        $cacheKey = "sub_remind:{$key}";
        if (Cache::has($cacheKey)) {
            return false;
        }
        Cache::put($cacheKey, true, now()->addDays(3));

        return true;
    }

    private function notifyOffice(Office $office, string $message, IpPanelSmsService $sms): void
    {
        $manager = $office->users()->whereIn('role', ['office_manager', 'super_admin'])->first()
            ?? $office->users()->first();

        if (! $manager?->mobile) {
            return;
        }

        try {
            $sms->sendPlain($manager->mobile, $message);
        } catch (\Throwable) {
            // logged in SMS service
        }
    }
}
