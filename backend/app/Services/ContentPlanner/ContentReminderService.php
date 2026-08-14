<?php

namespace App\Services\ContentPlanner;

use App\Contracts\SmsProviderInterface;
use App\Models\ContentPlannerAudit;
use App\Models\ContentPlannerUserSetting;
use App\Models\ContentReminder;
use App\Models\SocialContent;
use App\Models\User;
use App\Services\Crm\CrmCommunicationService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ContentReminderService
{
    public function __construct(
        private readonly DateTimeService $dates,
        private readonly SmsProviderInterface $sms,
        private readonly CrmCommunicationService $notifications,
    ) {}

    public function effectiveOffsetMinutes(SocialContent $content): int
    {
        if ($content->reminder_custom_minutes !== null) {
            return (int) $content->reminder_custom_minutes;
        }

        return (int) ($content->reminder_offset_minutes ?? 0);
    }

    public function syncForContent(SocialContent $content): void
    {
        ContentReminder::where('content_id', $content->id)
            ->whereIn('status', ['pending', 'processing', 'failed'])
            ->update(['status' => 'cancelled', 'failure_reason' => 'rescheduled']);

        if (! $content->reminder_enabled || ! $content->isScheduledLike() || ! $content->scheduled_at_utc) {
            return;
        }

        $offset = $this->effectiveOffsetMinutes($content);
        $publishUtc = CarbonImmutable::parse($content->scheduled_at_utc->format('Y-m-d H:i:s'), 'UTC');
        $reminderAt = $publishUtc->subMinutes($offset);
        $key = hash('sha256', implode('|', [
            $content->id,
            $publishUtc->toIso8601String(),
            (string) $offset,
            'sms',
        ]));

        ContentReminder::updateOrCreate(
            ['idempotency_key' => $key],
            [
                'content_id' => $content->id,
                'office_id' => $content->office_id,
                'user_id' => $content->user_id,
                'reminder_type' => 'publish',
                'channel' => 'sms',
                'offset_minutes' => $offset,
                'scheduled_at_utc' => $reminderAt->utc()->format('Y-m-d H:i:s'),
                'status' => 'pending',
                'failure_reason' => null,
                'provider_response' => null,
                'attempt_count' => 0,
                'sent_at_utc' => null,
                'locked_at' => null,
                'locked_by' => null,
            ]
        );
    }

    public function cancelForContent(SocialContent $content, string $reason = 'cancelled'): void
    {
        ContentReminder::where('content_id', $content->id)
            ->whereIn('status', ['pending', 'processing', 'failed'])
            ->update(['status' => 'cancelled', 'failure_reason' => $reason]);
    }

    /**
     * Dispatch due reminders. Safe for concurrent workers via atomic claim.
     */
    public function processDue(int $limit = 50): array
    {
        $now = $this->dates->nowUtc();
        $ids = ContentReminder::query()
            ->where('status', 'pending')
            ->where('scheduled_at_utc', '<=', $now)
            ->orderBy('scheduled_at_utc')
            ->limit($limit)
            ->pluck('id');

        $stats = ['claimed' => 0, 'sent' => 0, 'failed' => 0, 'skipped' => 0];

        foreach ($ids as $id) {
            $reminder = $this->claim((int) $id);
            if (! $reminder) {
                $stats['skipped']++;
                continue;
            }
            $stats['claimed']++;
            $result = $this->send($reminder);
            if ($result === 'sent') {
                $stats['sent']++;
            } elseif ($result === 'failed') {
                $stats['failed']++;
            } else {
                $stats['skipped']++;
            }
        }

        return $stats;
    }

    public function claim(int $id): ?ContentReminder
    {
        return DB::transaction(function () use ($id) {
            $reminder = ContentReminder::query()->whereKey($id)->lockForUpdate()->first();
            if (! $reminder || $reminder->status !== 'pending') {
                return null;
            }
            if ($reminder->status === 'sent') {
                return null;
            }

            $reminder->update([
                'status' => 'processing',
                'locked_at' => now(),
                'locked_by' => gethostname().':'.getmypid(),
            ]);

            return $reminder->fresh();
        });
    }

    public function send(ContentReminder $reminder): string
    {
        $reminder->refresh();
        if ($reminder->status === 'sent') {
            return 'skipped';
        }

        $content = SocialContent::withTrashed()->find($reminder->content_id);
        if (! $content || $content->trashed() || in_array($content->status, ['cancelled', 'draft'], true)) {
            $reminder->update(['status' => 'cancelled', 'failure_reason' => 'content_inactive']);

            return 'skipped';
        }

        // Missed reminder recovery: for "at publish" only send if still not published
        $now = $this->dates->nowUtc();
        $publishUtc = $content->scheduled_at_utc
            ? CarbonImmutable::parse($content->scheduled_at_utc->toIso8601String(), 'UTC')
            : null;

        if ($publishUtc && $reminder->offset_minutes === 0 && $content->status === 'published') {
            $reminder->update(['status' => 'cancelled', 'failure_reason' => 'already_published']);

            return 'skipped';
        }

        // If reminder is very stale (> 24h after publish) and content published → skip
        if ($publishUtc && $now->greaterThan($publishUtc->addDay()) && $content->status === 'published') {
            $reminder->update(['status' => 'cancelled', 'failure_reason' => 'stale_after_publish']);

            return 'skipped';
        }

        $user = User::find($reminder->user_id);
        $settings = ContentPlannerUserSetting::where('user_id', $reminder->user_id)->first();
        $smsEnabled = $settings?->sms_reminder_enabled ?? true;
        $inAppEnabled = $settings?->in_app_notification_enabled ?? true;
        $mobile = $settings?->sms_mobile ?: $user?->mobile;

        $tehran = $this->dates->utcToTehran($content->scheduled_at_utc);
        $jalaliDate = $this->dates->formatJalaliDate($tehran) ?? '';
        $time = $this->dates->formatJalaliTime($tehran) ?? '';
        $platforms = implode('، ', array_map(
            fn ($p) => (string) (config('content_planner.platforms.'.$p) ?? $p),
            $content->platforms ?? []
        ));

        if ($inAppEnabled && $user) {
            $this->notifications->notify(
                $user,
                $user,
                'system',
                'زمان انتشار محتوا رسیده است',
                "🔔 زمان انتشار محتوا رسیده است.\nعنوان:\n{$content->title}\nپلتفرم:\n{$platforms}\nزمان:\n{$jalaliDate} - {$time}",
                '/content-planner?id='.$content->id,
                ['content_id' => $content->id, 'category' => 'content_planner']
            );
        }

        if (! $smsEnabled) {
            $reminder->update([
                'status' => 'sent',
                'sent_at_utc' => $now,
                'failure_reason' => 'sms_disabled',
                'attempt_count' => $reminder->attempt_count + 1,
            ]);
            $this->markContentReminderSent($content);

            return 'sent';
        }

        if (! $this->isValidIranMobile((string) $mobile)) {
            $reminder->update([
                'status' => 'failed',
                'failure_reason' => 'invalid_phone',
                'attempt_count' => $reminder->attempt_count + 1,
                'provider_response' => json_encode(['error' => 'invalid_phone']),
            ]);

            return 'failed';
        }

        $template = (string) config('content_planner.sms_template');
        $message = str_replace(
            ['{title}', '{jalali_date}', '{time}', '{platform}'],
            [$content->title, $jalaliDate, $time, $platforms],
            $template
        );

        $attempts = (int) $reminder->attempt_count + 1;
        $max = (int) config('content_planner.max_sms_attempts', 3);
        $result = $this->sms->sendSms((string) $mobile, $message);

        Log::info('content_planner.sms_attempt', [
            'content_id' => $content->id,
            'user_id' => $reminder->user_id,
            'reminder_id' => $reminder->id,
            'attempt' => $attempts,
            'ok' => $result['ok'] ?? false,
            'error' => $result['error'] ?? null,
        ]);

        if ($result['ok'] ?? false) {
            $reminder->update([
                'status' => 'sent',
                'sent_at_utc' => $now,
                'attempt_count' => $attempts,
                'provider_response' => json_encode($result['raw'] ?? $result),
                'failure_reason' => null,
            ]);
            $this->markContentReminderSent($content);

            return 'sent';
        }

        if ($attempts >= $max) {
            $reminder->update([
                'status' => 'failed',
                'attempt_count' => $attempts,
                'failure_reason' => 'provider_error',
                'provider_response' => json_encode($result),
            ]);

            return 'failed';
        }

        // re-queue as pending for retry
        $reminder->update([
            'status' => 'pending',
            'attempt_count' => $attempts,
            'failure_reason' => 'provider_error',
            'provider_response' => json_encode($result),
            'locked_at' => null,
            'locked_by' => null,
        ]);

        return 'failed';
    }

    private function markContentReminderSent(SocialContent $content): void
    {
        if ($content->status === 'scheduled') {
            $content->update(['status' => 'reminder_sent']);
            ContentPlannerAudit::create([
                'content_id' => $content->id,
                'office_id' => $content->office_id,
                'user_id' => $content->user_id,
                'action' => 'reminder_sent',
                'meta' => ['via' => 'scheduler'],
                'created_at' => now(),
            ]);
        }
    }

    public function isValidIranMobile(string $mobile): bool
    {
        $digits = preg_replace('/\D+/', '', $mobile) ?? '';
        if (str_starts_with($digits, '98') && strlen($digits) === 12) {
            $digits = '0'.substr($digits, 2);
        }
        if (str_starts_with($digits, '9') && strlen($digits) === 10) {
            $digits = '0'.$digits;
        }

        return (bool) preg_match('/^09\d{9}$/', $digits);
    }
}
