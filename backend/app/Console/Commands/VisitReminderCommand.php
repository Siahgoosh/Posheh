<?php

namespace App\Console\Commands;

use App\Models\PropertyVisit;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class VisitReminderCommand extends Command
{
    protected $signature = 'visits:remind';

    protected $description = 'Send SMS reminders for upcoming property visits';

    public function handle(): int
    {
        $visits = PropertyVisit::where('status', 'scheduled')
            ->where('sms_reminder_sent', false)
            ->whereBetween('visit_at', [now(), now()->addHours(24)])
            ->with(['property', 'customer', 'assignee'])
            ->get();

        foreach ($visits as $visit) {
            Log::info('visit.reminder', [
                'visit_id' => $visit->id,
                'property' => $visit->property?->code,
                'at' => $visit->visit_at?->toIso8601String(),
            ]);
            $visit->update(['sms_reminder_sent' => true]);
        }

        $this->info("Processed {$visits->count()} visit reminders.");

        return self::SUCCESS;
    }
}
