<?php

namespace App\Console\Commands;

use App\Models\PropertyVisit;
use App\Services\Sms\IpPanelSmsService;
use Illuminate\Console\Command;

class VisitReminderCommand extends Command
{
    protected $signature = 'visits:remind';

    protected $description = 'Send SMS reminders for upcoming property visits';

    public function handle(IpPanelSmsService $sms): int
    {
        $visits = PropertyVisit::where('status', 'scheduled')
            ->where('sms_reminder_sent', false)
            ->whereBetween('visit_at', [now()->addHours(1), now()->addHours(24)])
            ->with(['property', 'customer', 'assignee'])
            ->get();

        $sent = 0;
        foreach ($visits as $visit) {
            $mobile = $visit->assignee?->mobile;
            if (! $mobile) {
                continue;
            }

            $code = $visit->property?->code ?? 'ملک';
            $time = $visit->visit_at?->format('H:i') ?? '';
            $customer = $visit->customer?->name ?? '';
            $message = "پوشه: یادآور بازدید — {$code}".($customer ? " ({$customer})" : '')." ساعت {$time}";

            try {
                $sms->sendPlain($mobile, $message);
                $visit->update(['sms_reminder_sent' => true]);
                $sent++;
            } catch (\Throwable) {
                // logged in SMS service
            }
        }

        $this->info("Sent {$sent} visit reminders.");

        return self::SUCCESS;
    }
}
