<?php

namespace App\Console\Commands;

use App\Models\Property;
use App\Models\PropertyVisit;
use App\Services\Sms\IpPanelSmsService;
use Illuminate\Console\Command;

class PropertyExpiryReminderCommand extends Command
{
    protected $signature = 'properties:remind-expiry';

    protected $description = 'SMS reminder for properties expiring in 3 days';

    public function handle(IpPanelSmsService $sms): int
    {
        $properties = Property::where('status', 'active')
            ->whereNotNull('expires_at')
            ->whereBetween('expires_at', [now()->addDays(2), now()->addDays(3)])
            ->with(['creator', 'office.users'])
            ->get();

        $sent = 0;
        foreach ($properties as $property) {
            $mobile = $property->creator?->mobile
                ?? $property->office?->users()->whereIn('role', ['office_manager'])->first()?->mobile;

            if (! $mobile) {
                continue;
            }

            $message = "پوشه: آگهی ملک {$property->code} تا ۳ روز دیگر منقضی می‌شود. برای تمدید وارد پنل شوید.";
            try {
                $sms->sendPlain($mobile, $message);
                $sent++;
            } catch (\Throwable) {
                // logged in SMS service
            }
        }

        $this->info("Sent {$sent} property expiry reminders.");

        return self::SUCCESS;
    }
}
