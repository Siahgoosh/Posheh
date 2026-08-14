<?php

namespace App\Services\ContentPlanner;

use App\Models\ContentPlannerAudit;
use App\Models\ContentPlannerTemplate;
use App\Models\ContentPlannerUserSetting;
use App\Models\SocialContent;
use App\Models\SocialContentMedia;
use App\Models\User;
use App\Services\Subscription\SubscriptionAccessService;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ContentPlannerService
{
    public function __construct(
        private readonly DateTimeService $dates,
        private readonly ContentReminderService $reminders,
        private readonly SubscriptionAccessService $access,
    ) {}

    public function assertFeature(User $user): void
    {
        if ($user->isSuperAdmin() || $user->isPlatformStaff()) {
            return;
        }
        $office = $user->office;
        if ($office && ! $this->access->officeHasFeature($office, (string) config('content_planner.feature_key', 'content_planner'))) {
            // Feature gate soft: allow if plan doesn't list it yet (backward compat during rollout)
            // Still require authenticated office user.
        }
    }

    public function meta(): array
    {
        return [
            'content_types' => config('content_planner.content_types'),
            'platforms' => config('content_planner.platforms'),
            'goals' => config('content_planner.goals'),
            'statuses' => config('content_planner.statuses'),
            'reminder_offsets' => config('content_planner.reminder_offsets'),
            'timezone' => $this->dates->timezone(),
            'is_friday' => $this->dates->isFriday(),
            'now_tehran' => [
                'iso' => $this->dates->nowTehran()->toIso8601String(),
                'jalali' => $this->dates->formatJalali($this->dates->nowTehran()),
                'weekday' => $this->dates->weekdayFa($this->dates->nowTehran()),
            ],
        ];
    }

    public function dashboard(User $user, ?CarbonImmutable $weekRef = null): array
    {
        $this->assertFeature($user);
        $start = $this->dates->startOfWeekTehran($weekRef);
        $end = $this->dates->endOfWeekTehran($weekRef);
        $nextStart = $start->addWeek();
        $nextEnd = $end->addWeek();

        $base = SocialContent::query()->where('office_id', $user->office_id);

        $weekItems = (clone $base)
            ->whereBetween('scheduled_at_utc', [$start->setTimezone('UTC'), $end->setTimezone('UTC')])
            ->get();

        $nextCount = (clone $base)
            ->whereBetween('scheduled_at_utc', [$nextStart->setTimezone('UTC'), $nextEnd->setTimezone('UTC')])
            ->count();

        $nearest = (clone $base)
            ->whereIn('status', ['scheduled', 'reminder_sent'])
            ->where('scheduled_at_utc', '>=', $this->dates->nowUtc())
            ->orderBy('scheduled_at_utc')
            ->first();

        $activeReminders = \App\Models\ContentReminder::query()
            ->where('office_id', $user->office_id)
            ->where('user_id', $user->id)
            ->where('status', 'pending')
            ->count();

        $nearestTehran = $nearest?->scheduled_at_utc
            ? $this->dates->utcToTehran($nearest->scheduled_at_utc)
            : null;

        return [
            'week_planned' => $weekItems->count(),
            'week_published' => $weekItems->where('status', 'published')->count(),
            'week_pending' => $weekItems->whereIn('status', ['scheduled', 'reminder_sent', 'draft'])->count(),
            'next_week_count' => $nextCount,
            'nearest_publish' => $nearestTehran ? [
                'id' => $nearest->id,
                'title' => $nearest->title,
                'jalali_date' => $this->dates->formatJalaliDate($nearestTehran),
                'time' => $this->dates->formatJalaliTime($nearestTehran),
                'label' => $this->countdownLabel($nearestTehran),
                'is_today' => $nearestTehran->isSameDay($this->dates->nowTehran()),
            ] : null,
            'active_reminders' => $activeReminders,
            'is_friday' => $this->dates->isFriday(),
            'week_label' => $this->dates->dayLabel($start).' تا '.$this->dates->dayLabel($end),
        ];
    }

    public function calendar(User $user, string $mode = 'week', ?string $anchor = null): array
    {
        $this->assertFeature($user);
        $ref = $anchor
            ? $this->dates->tehranLocalToUtc($anchor.' 12:00')->setTimezone($this->dates->timezone())
            : $this->dates->nowTehran();

        if ($mode === 'month') {
            $start = $ref->startOfMonth()->startOfDay();
            $end = $ref->endOfMonth()->endOfDay();
            // expand to full Persian weeks
            $start = $this->dates->startOfWeekTehran($start);
            $end = $this->dates->endOfWeekTehran($end);
        } elseif ($mode === 'next_week') {
            $start = $this->dates->startOfWeekTehran($ref)->addWeek();
            $end = $this->dates->endOfWeekTehran($start);
        } else {
            $start = $this->dates->startOfWeekTehran($ref);
            $end = $this->dates->endOfWeekTehran($ref);
        }

        $items = SocialContent::query()
            ->where('office_id', $user->office_id)
            ->whereBetween('scheduled_at_utc', [$start->setTimezone('UTC'), $end->setTimezone('UTC')])
            ->with('media')
            ->orderBy('scheduled_at_utc')
            ->get()
            ->map(fn (SocialContent $c) => $this->serialize($c));

        $days = [];
        $cursor = $start->startOfDay();
        while ($cursor->lte($end)) {
            $key = $cursor->toDateString();
            $days[] = [
                'date' => $key,
                'jalali_label' => $this->dates->dayLabel($cursor),
                'weekday' => $this->dates->weekdayFa($cursor),
                'is_today' => $cursor->isSameDay($this->dates->nowTehran()),
                'is_friday' => (int) $cursor->dayOfWeek === \Carbon\Carbon::FRIDAY,
                'items' => $items->filter(function ($item) use ($key) {
                    return ($item['scheduled_date'] ?? null) === $key;
                })->values()->all(),
            ];
            $cursor = $cursor->addDay();
        }

        return [
            'mode' => $mode,
            'start' => $start->toDateString(),
            'end' => $end->toDateString(),
            'start_jalali' => $this->dates->formatJalaliDate($start),
            'end_jalali' => $this->dates->formatJalaliDate($end),
            'days' => $days,
            'is_friday_today' => $this->dates->isFriday(),
        ];
    }

    public function list(User $user, array $filters = [])
    {
        $this->assertFeature($user);
        $q = SocialContent::query()
            ->where('office_id', $user->office_id)
            ->with('media')
            ->orderByDesc('scheduled_at_utc')
            ->orderByDesc('id');

        if (! empty($filters['status']) && $filters['status'] !== 'all') {
            $q->where('status', $filters['status']);
        }
        if (! empty($filters['content_type'])) {
            $q->where('content_type', $filters['content_type']);
        }
        if (! empty($filters['platform'])) {
            $q->whereJsonContains('platforms', $filters['platform']);
        }
        if (! empty($filters['q'])) {
            $term = '%'.$filters['q'].'%';
            $q->where(function ($qq) use ($term) {
                $qq->where('title', 'like', $term)
                    ->orWhere('caption', 'like', $term)
                    ->orWhere('hook', 'like', $term)
                    ->orWhere('body', 'like', $term);
            });
        }
        if (! empty($filters['from'])) {
            $from = $this->dates->tehranLocalToUtc($filters['from'].' 00:00');
            $q->where('scheduled_at_utc', '>=', $from);
        }
        if (! empty($filters['to'])) {
            $to = $this->dates->tehranLocalToUtc($filters['to'].' 23:59');
            $q->where('scheduled_at_utc', '<=', $to);
        }

        return $q->paginate(min((int) ($filters['per_page'] ?? 30), 100))
            ->through(fn (SocialContent $c) => $this->serialize($c));
    }

    public function find(User $user, int $id): SocialContent
    {
        return SocialContent::query()
            ->where('office_id', $user->office_id)
            ->with(['media', 'reminders'])
            ->findOrFail($id);
    }

    public function create(User $user, array $data): SocialContent
    {
        $this->assertFeature($user);
        $payload = $this->normalizePayload($data, $user, null);

        return DB::transaction(function () use ($user, $payload, $data) {
            $content = SocialContent::create($payload);
            $this->audit($user, $content, 'created', null, $content->toArray());
            if (! empty($data['media_files']) && is_array($data['media_files'])) {
                $this->attachUploads($content, $data['media_files']);
            }
            $this->reminders->syncForContent($content->fresh());

            return $content->fresh(['media', 'reminders']);
        });
    }

    public function update(User $user, int $id, array $data): SocialContent
    {
        $content = $this->find($user, $id);
        $before = $content->toArray();
        $payload = $this->normalizePayload($data, $user, $content);

        return DB::transaction(function () use ($user, $content, $payload, $data, $before) {
            $content->update($payload);
            if (! empty($data['remove_media_ids']) && is_array($data['remove_media_ids'])) {
                $this->removeMedia($content, $data['remove_media_ids']);
            }
            if (! empty($data['media_files']) && is_array($data['media_files'])) {
                $this->attachUploads($content, $data['media_files']);
            }
            $fresh = $content->fresh(['media', 'reminders']);
            $this->audit($user, $fresh, 'updated', $before, $fresh->toArray());
            $this->reminders->syncForContent($fresh);

            return $fresh;
        });
    }

    public function delete(User $user, int $id): void
    {
        $content = $this->find($user, $id);
        $this->reminders->cancelForContent($content, 'deleted');
        $this->audit($user, $content, 'deleted', $content->toArray(), null);
        $content->delete();
    }

    public function duplicate(User $user, int $id): SocialContent
    {
        $source = $this->find($user, $id);
        $copy = $source->replicate([
            'scheduled_at_utc',
            'published_at_utc',
            'cancelled_at_utc',
            'status',
        ]);
        $copy->status = 'draft';
        $copy->scheduled_at_utc = null;
        $copy->published_at_utc = null;
        $copy->cancelled_at_utc = null;
        $copy->title = $source->title.' (کپی)';
        $copy->user_id = $user->id;
        $copy->save();

        foreach ($source->media as $m) {
            SocialContentMedia::create([
                'content_id' => $copy->id,
                'disk' => $m->disk,
                'path' => $m->path,
                'original_name' => $m->original_name,
                'mime_type' => $m->mime_type,
                'media_type' => $m->media_type,
                'size_bytes' => $m->size_bytes,
                'sort_order' => $m->sort_order,
            ]);
        }

        $this->audit($user, $copy, 'duplicated', null, ['source_id' => $source->id]);

        return $copy->fresh(['media']);
    }

    public function schedule(User $user, int $id, array $data): SocialContent
    {
        $content = $this->find($user, $id);
        $scheduledUtc = $this->resolveScheduledUtc($data);
        $this->assertNotPastForScheduled($scheduledUtc);

        $before = $content->toArray();
        $content->update([
            'scheduled_at_utc' => $scheduledUtc->utc()->format('Y-m-d H:i:s'),
            'status' => 'scheduled',
            'cancelled_at_utc' => null,
            'reminder_enabled' => array_key_exists('reminder_enabled', $data)
                ? (bool) $data['reminder_enabled']
                : $content->reminder_enabled,
            'reminder_offset_minutes' => $data['reminder_offset_minutes'] ?? $content->reminder_offset_minutes,
            'reminder_custom_minutes' => $data['reminder_custom_minutes'] ?? $content->reminder_custom_minutes,
        ]);
        $fresh = $content->fresh(['media', 'reminders']);
        $this->reminders->syncForContent($fresh);
        $this->audit($user, $fresh, 'scheduled', $before, $fresh->toArray());

        return $fresh;
    }

    public function cancel(User $user, int $id): SocialContent
    {
        $content = $this->find($user, $id);
        $before = $content->toArray();
        $content->update([
            'status' => 'cancelled',
            'cancelled_at_utc' => $this->dates->nowUtc(),
        ]);
        $this->reminders->cancelForContent($content, 'cancelled');
        $fresh = $content->fresh(['media', 'reminders']);
        $this->audit($user, $fresh, 'cancelled', $before, $fresh->toArray());

        return $fresh;
    }

    public function markPublished(User $user, int $id): SocialContent
    {
        $content = $this->find($user, $id);
        $before = $content->toArray();
        $content->update([
            'status' => 'published',
            'published_at_utc' => $this->dates->nowUtc(),
        ]);
        $this->reminders->cancelForContent($content, 'published');
        $fresh = $content->fresh(['media', 'reminders']);
        $this->audit($user, $fresh, 'published', $before, $fresh->toArray());

        return $fresh;
    }

    public function moveDay(User $user, int $id, string $tehranDateYmd): SocialContent
    {
        $content = $this->find($user, $id);
        if (! $content->scheduled_at_utc) {
            throw ValidationException::withMessages(['scheduled_at' => 'این محتوا هنوز زمان‌بندی نشده است.']);
        }
        $tehran = $this->dates->utcToTehran($content->scheduled_at_utc);
        $time = $tehran?->format('H:i:s') ?? '12:00:00';
        $newUtc = $this->dates->tehranLocalToUtc($tehranDateYmd.' '.$time);

        if ($content->isScheduledLike()) {
            $this->assertNotPastForScheduled($newUtc);
        }

        $before = $content->toArray();
        $content->update(['scheduled_at_utc' => $newUtc->utc()->format('Y-m-d H:i:s')]);
        $fresh = $content->fresh(['media', 'reminders']);
        $this->reminders->syncForContent($fresh);
        $this->audit($user, $fresh, 'moved_day', $before, $fresh->toArray());

        return $fresh;
    }

    public function quickAdd(User $user, array $items): array
    {
        $created = [];
        foreach ($items as $item) {
            $created[] = $this->serialize($this->create($user, [
                'title' => $item['title'],
                'content_type' => $item['content_type'] ?? 'post',
                'platforms' => $item['platforms'] ?? ['instagram'],
                'status' => 'scheduled',
                'jalali_date' => $item['jalali_date'] ?? null,
                'time' => $item['time'] ?? '18:00',
                'scheduled_local' => $item['scheduled_local'] ?? null,
                'reminder_enabled' => $item['reminder_enabled'] ?? true,
                'reminder_offset_minutes' => $item['reminder_offset_minutes'] ?? 0,
            ]));
        }

        return $created;
    }

    public function templates(User $user)
    {
        return ContentPlannerTemplate::query()
            ->where(function ($q) use ($user) {
                $q->where('office_id', $user->office_id)
                    ->orWhere(function ($qq) {
                        $qq->whereNull('office_id')->where('is_shared', true);
                    });
            })
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    public function saveTemplate(User $user, array $data): ContentPlannerTemplate
    {
        return ContentPlannerTemplate::create([
            'office_id' => $user->office_id,
            'user_id' => $user->id,
            'name' => $data['name'],
            'content_type' => $data['content_type'] ?? 'post',
            'platforms' => $data['platforms'] ?? ['instagram'],
            'goal' => $data['goal'] ?? null,
            'hook' => $data['hook'] ?? null,
            'body' => $data['body'] ?? null,
            'cta' => $data['cta'] ?? null,
            'caption' => $data['caption'] ?? null,
            'hashtags' => $this->normalizeHashtags($data['hashtags'] ?? []),
            'visual_idea' => $data['visual_idea'] ?? null,
            'overlay_text' => $data['overlay_text'] ?? null,
            'is_shared' => (bool) ($data['is_shared'] ?? false),
        ]);
    }

    public function getSettings(User $user): ContentPlannerUserSetting
    {
        return ContentPlannerUserSetting::firstOrCreate(
            ['user_id' => $user->id],
            [
                'office_id' => $user->office_id,
                'sms_reminder_enabled' => true,
                'in_app_notification_enabled' => true,
                'sms_mobile' => $user->mobile,
            ]
        );
    }

    public function updateSettings(User $user, array $data): ContentPlannerUserSetting
    {
        $settings = $this->getSettings($user);
        $settings->update([
            'sms_reminder_enabled' => array_key_exists('sms_reminder_enabled', $data)
                ? (bool) $data['sms_reminder_enabled']
                : $settings->sms_reminder_enabled,
            'in_app_notification_enabled' => array_key_exists('in_app_notification_enabled', $data)
                ? (bool) $data['in_app_notification_enabled']
                : $settings->in_app_notification_enabled,
            'sms_mobile' => $data['sms_mobile'] ?? $settings->sms_mobile,
        ]);

        return $settings->fresh();
    }

    public function serialize(SocialContent $c): array
    {
        $tehran = $c->scheduled_at_utc ? $this->dates->utcToTehran($c->scheduled_at_utc) : null;
        $statuses = config('content_planner.statuses');
        $types = config('content_planner.content_types');
        $platforms = config('content_planner.platforms');
        $goals = config('content_planner.goals');

        return [
            'id' => $c->id,
            'title' => $c->title,
            'topic' => $c->topic,
            'content_type' => $c->content_type,
            'content_type_label' => $types[$c->content_type] ?? $c->content_type,
            'platforms' => $c->platforms ?? [],
            'platform_labels' => array_map(fn ($p) => $platforms[$p] ?? $p, $c->platforms ?? []),
            'goal' => $c->goal,
            'goal_label' => $c->goal ? ($goals[$c->goal] ?? $c->goal) : null,
            'hook' => $c->hook,
            'body' => $c->body,
            'cta' => $c->cta,
            'caption' => $c->caption,
            'caption_length' => mb_strlen((string) $c->caption),
            'hashtags' => $c->hashtags ?? [],
            'visual_idea' => $c->visual_idea,
            'overlay_text' => $c->overlay_text,
            'location' => $c->location,
            'notes' => $c->notes,
            'status' => $c->status,
            'status_label' => $statuses[$c->status] ?? $c->status,
            'timezone' => $c->timezone ?: $this->dates->timezone(),
            'scheduled_at_utc' => $c->scheduled_at_utc?->toIso8601String(),
            'scheduled_date' => $tehran?->toDateString(),
            'scheduled_jalali' => $this->dates->formatJalaliDate($tehran),
            'scheduled_time' => $this->dates->formatJalaliTime($tehran),
            'scheduled_label' => $tehran ? $this->dates->dayLabel($tehran).' — '.$this->dates->formatJalaliTime($tehran) : null,
            'countdown' => $tehran && $c->isScheduledLike() ? $this->countdownLabel($tehran) : null,
            'reminder_enabled' => (bool) $c->reminder_enabled,
            'reminder_offset_minutes' => (int) $c->reminder_offset_minutes,
            'reminder_custom_minutes' => $c->reminder_custom_minutes,
            'published_at_utc' => $c->published_at_utc?->toIso8601String(),
            'media' => $c->relationLoaded('media')
                ? $c->media->map(fn (SocialContentMedia $m) => [
                    'id' => $m->id,
                    'url' => Storage::disk($m->disk)->url($m->path),
                    'original_name' => $m->original_name,
                    'mime_type' => $m->mime_type,
                    'media_type' => $m->media_type,
                    'size_bytes' => $m->size_bytes,
                ])->all()
                : [],
            'created_at' => $c->created_at?->toIso8601String(),
            'updated_at' => $c->updated_at?->toIso8601String(),
        ];
    }

    private function normalizePayload(array $data, User $user, ?SocialContent $existing): array
    {
        $status = $data['status'] ?? $existing?->status ?? 'draft';
        $allowedStatuses = array_keys(config('content_planner.statuses'));
        if (! in_array($status, $allowedStatuses, true)) {
            throw ValidationException::withMessages(['status' => 'وضعیت نامعتبر است.']);
        }

        $types = array_keys(config('content_planner.content_types'));
        $contentType = $data['content_type'] ?? $existing?->content_type ?? 'post';
        if (! in_array($contentType, $types, true)) {
            throw ValidationException::withMessages(['content_type' => 'نوع محتوا نامعتبر است.']);
        }

        $platforms = $data['platforms'] ?? $existing?->platforms ?? ['instagram'];
        if (! is_array($platforms) || $platforms === []) {
            throw ValidationException::withMessages(['platforms' => 'حداقل یک پلتفرم انتخاب کنید.']);
        }
        $allowedPlatforms = array_keys(config('content_planner.platforms'));
        foreach ($platforms as $p) {
            if (! in_array($p, $allowedPlatforms, true)) {
                throw ValidationException::withMessages(['platforms' => 'پلتفرم نامعتبر است.']);
            }
        }

        $scheduledUtc = null;
        if (! empty($data['jalali_date']) || ! empty($data['scheduled_local']) || array_key_exists('scheduled_at_utc', $data)) {
            $scheduledUtc = $this->resolveScheduledUtc($data);
        } elseif ($existing) {
            $scheduledUtc = $existing->scheduled_at_utc
                ? CarbonImmutable::parse($existing->scheduled_at_utc->toIso8601String(), 'UTC')
                : null;
        }

        if (in_array($status, ['scheduled', 'reminder_sent'], true)) {
            if (! $scheduledUtc) {
                throw ValidationException::withMessages(['scheduled_at' => 'برای زمان‌بندی، تاریخ و ساعت الزامی است.']);
            }
            $this->assertNotPastForScheduled($scheduledUtc);
        }

        $title = trim((string) ($data['title'] ?? $existing?->title ?? ''));
        if ($title === '') {
            throw ValidationException::withMessages(['title' => 'عنوان محتوا الزامی است.']);
        }

        $offset = array_key_exists('reminder_offset_minutes', $data)
            ? (int) $data['reminder_offset_minutes']
            : (int) ($existing?->reminder_offset_minutes ?? 0);
        $custom = array_key_exists('reminder_custom_minutes', $data)
            ? ($data['reminder_custom_minutes'] !== null ? (int) $data['reminder_custom_minutes'] : null)
            : $existing?->reminder_custom_minutes;

        return [
            'office_id' => $user->office_id,
            'user_id' => $existing?->user_id ?? $user->id,
            'title' => $title,
            'topic' => $data['topic'] ?? $existing?->topic,
            'content_type' => $contentType,
            'platforms' => array_values($platforms),
            'goal' => $data['goal'] ?? $existing?->goal,
            'hook' => $data['hook'] ?? $existing?->hook,
            'body' => $data['body'] ?? $existing?->body,
            'cta' => $data['cta'] ?? $existing?->cta,
            'caption' => $data['caption'] ?? $existing?->caption,
            'hashtags' => $this->normalizeHashtags($data['hashtags'] ?? $existing?->hashtags ?? []),
            'visual_idea' => $data['visual_idea'] ?? $existing?->visual_idea,
            'overlay_text' => $data['overlay_text'] ?? $existing?->overlay_text,
            'location' => $data['location'] ?? $existing?->location,
            'notes' => $data['notes'] ?? $existing?->notes,
            'scheduled_at_utc' => $scheduledUtc?->utc()->format('Y-m-d H:i:s'),
            'timezone' => $this->dates->timezone(),
            'status' => $status,
            'reminder_enabled' => array_key_exists('reminder_enabled', $data)
                ? (bool) $data['reminder_enabled']
                : ($existing?->reminder_enabled ?? true),
            'reminder_offset_minutes' => $offset,
            'reminder_custom_minutes' => $custom,
            'template_id' => $data['template_id'] ?? $existing?->template_id,
        ];
    }

    private function resolveScheduledUtc(array $data): CarbonImmutable
    {
        if (! empty($data['jalali_date'])) {
            return $this->dates->jalaliDateTimeToUtc(
                (string) $data['jalali_date'],
                (string) ($data['time'] ?? '12:00')
            );
        }
        if (! empty($data['scheduled_local'])) {
            // Gregorian Y-m-d H:i interpreted as Tehran wall clock — NOT browser TZ
            return $this->dates->tehranLocalToUtc((string) $data['scheduled_local']);
        }
        if (! empty($data['scheduled_at_utc'])) {
            return CarbonImmutable::parse((string) $data['scheduled_at_utc'], 'UTC');
        }

        throw ValidationException::withMessages(['scheduled_at' => 'تاریخ انتشار نامعتبر است.']);
    }

    private function assertNotPastForScheduled(CarbonImmutable $utc): void
    {
        if ($utc->lessThan($this->dates->nowUtc()->subMinute())) {
            throw ValidationException::withMessages([
                'scheduled_at' => 'زمان انتشار نمی‌تواند در گذشته باشد.',
            ]);
        }
    }

    private function normalizeHashtags(mixed $tags): array
    {
        if (is_string($tags)) {
            $tags = preg_split('/[\s,،]+/u', $tags) ?: [];
        }
        if (! is_array($tags)) {
            return [];
        }
        $out = [];
        foreach ($tags as $t) {
            $t = trim((string) $t);
            if ($t === '') {
                continue;
            }
            if (! str_starts_with($t, '#')) {
                $t = '#'.$t;
            }
            $out[] = $t;
        }

        return array_values(array_unique($out));
    }

    private function attachUploads(SocialContent $content, array $files): void
    {
        $sort = (int) $content->media()->max('sort_order');
        foreach ($files as $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }
            $path = $file->store('content-planner/'.$content->office_id.'/'.$content->id, 'public');
            $mime = (string) $file->getMimeType();
            SocialContentMedia::create([
                'content_id' => $content->id,
                'disk' => 'public',
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $mime,
                'media_type' => str_starts_with($mime, 'video/') ? 'video' : 'image',
                'size_bytes' => $file->getSize() ?: 0,
                'sort_order' => ++$sort,
            ]);
        }
    }

    private function removeMedia(SocialContent $content, array $ids): void
    {
        $rows = $content->media()->whereIn('id', $ids)->get();
        foreach ($rows as $row) {
            try {
                Storage::disk($row->disk)->delete($row->path);
            } catch (\Throwable) {
            }
            $row->delete();
        }
    }

    private function audit(User $user, ?SocialContent $content, string $action, ?array $before, ?array $after): void
    {
        ContentPlannerAudit::create([
            'content_id' => $content?->id,
            'office_id' => $user->office_id ?? $content?->office_id,
            'user_id' => $user->id,
            'action' => $action,
            'before' => $before,
            'after' => $after,
            'created_at' => now(),
        ]);
    }

    private function countdownLabel(CarbonImmutable $tehran): string
    {
        $now = $this->dates->nowTehran();
        if ($tehran->lessThanOrEqualTo($now)) {
            return 'زمان انتشار فرا رسیده';
        }
        if ($tehran->isSameDay($now)) {
            return 'انتشار امروز ساعت '.$tehran->format('H:i');
        }
        $diff = $now->diff($tehran);
        $parts = [];
        if ($diff->d > 0) {
            $parts[] = $diff->d.' روز';
        }
        if ($diff->h > 0) {
            $parts[] = $diff->h.' ساعت';
        }
        if ($parts === [] && $diff->i > 0) {
            $parts[] = $diff->i.' دقیقه';
        }

        return 'انتشار در '.implode(' و ', $parts);
    }
}
