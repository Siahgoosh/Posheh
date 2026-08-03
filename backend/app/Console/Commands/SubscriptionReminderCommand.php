<?php

namespace App\Console\Commands;

use App\Models\Office;
use App\Services\Notification\UserNotificationService;
use App\Services\Sms\IpPanelSmsService;
use App\Services\Subscription\SubscriptionAccessService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class SubscriptionReminderCommand extends Command
{
    protected $signature = 'subscriptions:remind';

    protected $description = 'Send SMS reminders for trial/subscription expiry';

    public function handle(SubscriptionAccessService $access, IpPanelSmsService $sms, UserNotificationService $notifications): int
    {
        $offices = Office::with('users')->where('is_active', true)->get();
        $sent = 0;

        foreach ($offices as $office) {
            if ($access->hasAccess($office)) {
                $daysLeft = $access->trialDaysRemaining($office);
                if ($office->onTrial() && in_array($daysLeft, [1, 2, 3], true)) {
                    $this->notifyOffice($office, "دوره آزمایشی پوشه {$daysLeft} روز دیگر تمام می‌شود. برای تمدید وارد پنل شوید.", $sms, $notifications);
                    $sent++;
                }
                continue;
            }

            $cacheKey = "sub_remind:{$office->id}:".now()->toDateString();
            if (Cache::has($cacheKey)) {
                continue;
            }

            $this->notifyOffice($office, 'اشتراک پوشه شما منقضی شده. لطفاً از بخش تمدید حساب را شارژ کنید.', $sms, $notifications);
            Cache::put($cacheKey, true, now()->addDay());
            $sent++;
        }

        $this->info("Sent {$sent} reminders.");

        return self::SUCCESS;
    }

    private function notifyOffice(Office $office, string $message, IpPanelSmsService $sms, UserNotificationService $notifications): void
    {
        $manager = $office->users()->whereIn('role', ['office_manager', 'super_admin'])->first()
            ?? $office->users()->first();

        if (! $manager) {
            return;
        }

        try {
            $notifications->notify($manager, 'یادآوری اشتراک پوشه', $message, '/subscription', 'credit-card');
        } catch (\Throwable) {
            // non-blocking
        }

        if (! $manager->mobile) {
            return;
        }

        try {
            $sms->sendPlain($manager->mobile, $message);
        } catch (\Throwable) {
            // logged in SMS service
        }
    }
}
