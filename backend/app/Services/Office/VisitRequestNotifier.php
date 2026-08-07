<?php

namespace App\Services\Office;

use App\Models\Office;
use App\Models\OfficeVisitRequest;
use App\Models\Property;
use App\Models\User;
use App\Services\Notification\UserNotificationService;
use Illuminate\Support\Facades\Log;

class VisitRequestNotifier
{
    public function __construct(
        private readonly UserNotificationService $notifications,
        private readonly TelegramBotService $telegram,
    ) {}

    public function notify(Office $office, OfficeVisitRequest $request, array $payload): void
    {
        $propertyCode = $request->property_id
            ? Property::where('id', $request->property_id)->value('code')
            : null;

        $schedule = trim(implode(' ', array_filter([
            $payload['preferred_date'] ?? null,
            isset($payload['preferred_time']) ? 'ساعت '.$payload['preferred_time'] : null,
        ]))) ?: 'زمان نامشخص';

        $body = $propertyCode
            ? "درخواست بازدید برای کد {$propertyCode} از {$payload['name']}"
            : "درخواست بازدید عمومی از {$payload['name']}";

        $detail = "{$body} — {$schedule}";

        User::where('office_id', $office->id)
            ->where('is_active', true)
            ->whereIn('role', ['office_manager', 'consultant', 'super_admin'])
            ->get()
            ->each(fn (User $user) => $this->notifications->notify(
                $user,
                'درخواست بازدید وبسایت',
                $detail,
                '/visits',
                'calendar',
            ));

        try {
            $this->telegram->notifyVisitRequest($office, $request, $propertyCode, $schedule);
        } catch (\Throwable $e) {
            Log::warning('Telegram visit notification failed', [
                'office_id' => $office->id,
                'request_id' => $request->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
