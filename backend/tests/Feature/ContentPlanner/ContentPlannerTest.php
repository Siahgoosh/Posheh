<?php

namespace Tests\Feature\ContentPlanner;

use App\Contracts\SmsProviderInterface;
use App\Enums\UserRole;
use App\Models\ContentReminder;
use App\Models\Office;
use App\Models\SocialContent;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\Wallet;
use App\Services\ContentPlanner\ContentPlannerService;
use App\Services\ContentPlanner\ContentReminderService;
use App\Services\ContentPlanner\DateTimeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ContentPlannerTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;

    private User $other;

    private Office $office;

    private Office $otherOffice;

    private FakeSmsProvider $sms;

    protected function setUp(): void
    {
        parent::setUp();
        config(['content_planner.timezone' => 'Asia/Tehran']);
        config(['app.timezone' => 'UTC']);

        $this->sms = new FakeSmsProvider;
        $this->app->instance(SmsProviderInterface::class, $this->sms);

        $this->office = Office::create(['name' => 'CP Office', 'slug' => 'cp-office', 'is_active' => true]);
        $this->otherOffice = Office::create(['name' => 'Other', 'slug' => 'cp-other', 'is_active' => true]);
        Wallet::create(['office_id' => $this->office->id, 'balance' => 0]);
        Wallet::create(['office_id' => $this->otherOffice->id, 'balance' => 0]);

        $plan = SubscriptionPlan::create([
            'slug' => 'cp-plan',
            'name' => 'CP',
            'max_users' => 5,
            'max_properties' => 100,
            'storage_gb' => 5,
            'monthly_price' => 1,
            'features' => ['content_planner', 'crm'],
        ]);
        Subscription::create([
            'office_id' => $this->office->id,
            'subscription_plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
        ]);
        Subscription::create([
            'office_id' => $this->otherOffice->id,
            'subscription_plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
        ]);

        $this->manager = User::create([
            'name' => 'Manager',
            'mobile' => '09121112233',
            'office_id' => $this->office->id,
            'role' => UserRole::OfficeManager,
            'is_active' => true,
        ]);
        $this->other = User::create([
            'name' => 'Other',
            'mobile' => '09124445566',
            'office_id' => $this->otherOffice->id,
            'role' => UserRole::OfficeManager,
            'is_active' => true,
        ]);
    }

    public function test_create_draft(): void
    {
        Sanctum::actingAs($this->manager);
        $res = $this->postJson('/api/v1/content-planner', [
            'title' => 'پیش‌نویس تست',
            'content_type' => 'post',
            'platforms' => ['instagram'],
            'status' => 'draft',
            'hook' => 'هوک',
            'body' => 'بدنه',
            'cta' => 'دایرکت کن',
            'caption' => 'کپشن تست',
            'hashtags' => ['#املاک', 'لامرد'],
        ]);
        $res->assertCreated();
        $this->assertSame('draft', $res->json('data.status'));
        $this->assertContains('#املاک', $res->json('data.hashtags'));
        $this->assertContains('#لامرد', $res->json('data.hashtags'));
    }

    public function test_create_scheduled_with_tehran_time(): void
    {
        Sanctum::actingAs($this->manager);
        $dates = app(DateTimeService::class);
        $future = $dates->nowTehran()->addDays(3)->setTime(18, 30);
        $jalali = $dates->formatJalaliDate($future);

        $res = $this->postJson('/api/v1/content-planner', [
            'title' => 'پست زمان‌بندی',
            'content_type' => 'reels',
            'platforms' => ['instagram', 'telegram'],
            'status' => 'scheduled',
            'jalali_date' => $jalali,
            'time' => '18:30',
            'reminder_enabled' => true,
            'reminder_offset_minutes' => 30,
        ]);
        $res->assertCreated();
        $this->assertSame('scheduled', $res->json('data.status'));
        $this->assertSame('18:30', $res->json('data.scheduled_time'));
        $this->assertDatabaseHas('content_reminders', [
            'content_id' => $res->json('data.id'),
            'status' => 'pending',
            'offset_minutes' => 30,
        ]);
    }

    public function test_past_schedule_rejected_but_draft_allowed(): void
    {
        Sanctum::actingAs($this->manager);
        $res = $this->postJson('/api/v1/content-planner', [
            'title' => 'گذشته',
            'content_type' => 'post',
            'platforms' => ['instagram'],
            'status' => 'scheduled',
            'scheduled_local' => '2020-01-01 10:00',
        ]);
        $res->assertStatus(422);

        $draft = $this->postJson('/api/v1/content-planner', [
            'title' => 'گذشته پیش‌نویس',
            'content_type' => 'post',
            'platforms' => ['instagram'],
            'status' => 'draft',
            'scheduled_local' => '2020-01-01 10:00',
        ]);
        $draft->assertCreated();
    }

    public function test_duplicate_cancel_publish(): void
    {
        Sanctum::actingAs($this->manager);
        $svc = app(ContentPlannerService::class);
        $dates = app(DateTimeService::class);
        $local = $dates->nowTehran()->addDays(2)->format('Y-m-d H:i');

        $content = $svc->create($this->manager, [
            'title' => 'اصلی',
            'content_type' => 'post',
            'platforms' => ['whatsapp'],
            'status' => 'scheduled',
            'scheduled_local' => $local,
            'reminder_enabled' => true,
        ]);

        $copy = $this->postJson('/api/v1/content-planner/'.$content->id.'/duplicate');
        $copy->assertCreated();
        $this->assertSame('draft', $copy->json('data.status'));
        $this->assertNull($copy->json('data.scheduled_at_utc'));

        $this->postJson('/api/v1/content-planner/'.$content->id.'/cancel')->assertOk();
        $this->assertSame('cancelled', SocialContent::find($content->id)->status);
        $this->assertTrue(
            ContentReminder::where('content_id', $content->id)->where('status', 'cancelled')->exists()
        );

        $content2 = $svc->create($this->manager, [
            'title' => 'برای انتشار',
            'content_type' => 'post',
            'platforms' => ['instagram'],
            'status' => 'scheduled',
            'scheduled_local' => $dates->nowTehran()->addDays(4)->format('Y-m-d H:i'),
        ]);
        $this->postJson('/api/v1/content-planner/'.$content2->id.'/mark-published')->assertOk();
        $this->assertSame('published', SocialContent::find($content2->id)->status);
    }

    public function test_multi_tenant_isolation(): void
    {
        Sanctum::actingAs($this->manager);
        $svc = app(ContentPlannerService::class);
        $c = $svc->create($this->manager, [
            'title' => 'محرمانه',
            'content_type' => 'post',
            'platforms' => ['instagram'],
            'status' => 'draft',
        ]);

        Sanctum::actingAs($this->other);
        $this->getJson('/api/v1/content-planner/'.$c->id)->assertNotFound();
        $this->putJson('/api/v1/content-planner/'.$c->id, ['title' => 'هک'])->assertNotFound();
        $this->deleteJson('/api/v1/content-planner/'.$c->id)->assertNotFound();
    }

    public function test_sms_success_and_idempotent_claim(): void
    {
        $svc = app(ContentPlannerService::class);
        $dates = app(DateTimeService::class);
        $reminders = app(ContentReminderService::class);

        $content = $svc->create($this->manager, [
            'title' => 'یادآوری',
            'content_type' => 'post',
            'platforms' => ['instagram'],
            'status' => 'scheduled',
            'scheduled_local' => $dates->nowTehran()->addMinutes(5)->format('Y-m-d H:i'),
            'reminder_enabled' => true,
            'reminder_offset_minutes' => 0,
        ]);

        $reminder = ContentReminder::where('content_id', $content->id)->first();
        $this->assertNotNull($reminder);
        // force due
        $reminder->update(['scheduled_at_utc' => $dates->nowUtc()->subMinute()]);

        $a = $reminders->claim($reminder->id);
        $b = $reminders->claim($reminder->id);
        $this->assertNotNull($a);
        $this->assertNull($b);

        $result = $reminders->send($a);
        $this->assertSame('sent', $result);
        $this->assertSame(1, $this->sms->sentCount);

        // already sent — skip
        $again = $reminders->claim($reminder->id);
        $this->assertNull($again);
        $reminder->refresh();
        $this->assertSame('sent', $reminder->status);
        $this->assertSame(1, $this->sms->sentCount);
    }

    public function test_sms_retry_then_fail(): void
    {
        $this->sms->failTimes = 5;
        $svc = app(ContentPlannerService::class);
        $dates = app(DateTimeService::class);
        $reminders = app(ContentReminderService::class);

        $content = $svc->create($this->manager, [
            'title' => 'شکست SMS',
            'content_type' => 'post',
            'platforms' => ['instagram'],
            'status' => 'scheduled',
            'scheduled_local' => $dates->nowTehran()->addHour()->format('Y-m-d H:i'),
            'reminder_enabled' => true,
            'reminder_offset_minutes' => 0,
        ]);
        $reminder = ContentReminder::where('content_id', $content->id)->first();
        $reminder->update(['scheduled_at_utc' => $dates->nowUtc()->subMinute()]);

        for ($i = 0; $i < 3; $i++) {
            $claimed = $reminders->claim($reminder->id);
            $this->assertNotNull($claimed);
            $reminders->send($claimed);
            $reminder->refresh();
        }
        $this->assertSame('failed', $reminder->status);
        $this->assertSame(3, $reminder->attempt_count);
    }

    public function test_invalid_phone_fails_without_send(): void
    {
        $this->manager->update(['mobile' => '123']);
        $svc = app(ContentPlannerService::class);
        $dates = app(DateTimeService::class);
        $reminders = app(ContentReminderService::class);

        $content = $svc->create($this->manager, [
            'title' => 'شماره بد',
            'content_type' => 'post',
            'platforms' => ['instagram'],
            'status' => 'scheduled',
            'scheduled_local' => $dates->nowTehran()->addHour()->format('Y-m-d H:i'),
            'reminder_enabled' => true,
        ]);
        $reminder = ContentReminder::where('content_id', $content->id)->first();
        $reminder->update(['scheduled_at_utc' => $dates->nowUtc()->subMinute()]);
        $claimed = $reminders->claim($reminder->id);
        $reminders->send($claimed);
        $reminder->refresh();
        $this->assertSame('failed', $reminder->status);
        $this->assertSame('invalid_phone', $reminder->failure_reason);
        $this->assertSame(0, $this->sms->sentCount);
    }

    public function test_reschedule_invalidates_old_reminder(): void
    {
        $svc = app(ContentPlannerService::class);
        $dates = app(DateTimeService::class);

        $content = $svc->create($this->manager, [
            'title' => 'تغییر زمان',
            'content_type' => 'post',
            'platforms' => ['instagram'],
            'status' => 'scheduled',
            'scheduled_local' => $dates->nowTehran()->addDays(2)->setTime(18, 30)->format('Y-m-d H:i'),
            'reminder_enabled' => true,
            'reminder_offset_minutes' => 0,
        ]);
        $oldKey = ContentReminder::where('content_id', $content->id)->value('idempotency_key');

        $svc->update($this->manager, $content->id, [
            'scheduled_local' => $dates->nowTehran()->addDays(2)->setTime(20, 0)->format('Y-m-d H:i'),
            'status' => 'scheduled',
        ]);

        $pending = ContentReminder::where('content_id', $content->id)->where('status', 'pending')->get();
        $this->assertCount(1, $pending);
        $this->assertNotSame($oldKey, $pending->first()->idempotency_key);
        $tehran = $dates->utcToTehran($pending->first()->scheduled_at_utc);
        $this->assertSame('20:00', $tehran->format('H:i'));
    }

    public function test_cancel_stops_reminder_send(): void
    {
        $svc = app(ContentPlannerService::class);
        $dates = app(DateTimeService::class);
        $reminders = app(ContentReminderService::class);

        $content = $svc->create($this->manager, [
            'title' => 'لغو',
            'content_type' => 'post',
            'platforms' => ['instagram'],
            'status' => 'scheduled',
            'scheduled_local' => $dates->nowTehran()->addHour()->format('Y-m-d H:i'),
            'reminder_enabled' => true,
        ]);
        $reminder = ContentReminder::where('content_id', $content->id)->first();
        $svc->cancel($this->manager, $content->id);
        $reminder->update(['scheduled_at_utc' => $dates->nowUtc()->subMinute(), 'status' => 'pending']);
        // re-cancel properly then process
        app(ContentReminderService::class)->cancelForContent($content->fresh(), 'cancelled');
        $stats = $reminders->processDue();
        $this->assertSame(0, $this->sms->sentCount);
        $this->assertSame(0, $stats['sent']);
    }

    public function test_calendar_range_only(): void
    {
        Sanctum::actingAs($this->manager);
        $svc = app(ContentPlannerService::class);
        $dates = app(DateTimeService::class);
        $inWeek = $dates->nowTehran()->addHours(3);
        if ($inWeek->greaterThan($dates->endOfWeekTehran())) {
            $inWeek = $dates->endOfWeekTehran()->setTime(23, 0);
        }
        $svc->create($this->manager, [
            'title' => 'این هفته',
            'content_type' => 'post',
            'platforms' => ['instagram'],
            'status' => 'scheduled',
            'scheduled_local' => $inWeek->format('Y-m-d H:i'),
        ]);
        $svc->create($this->manager, [
            'title' => 'ماه بعد',
            'content_type' => 'post',
            'platforms' => ['instagram'],
            'status' => 'scheduled',
            'scheduled_local' => $dates->nowTehran()->addMonths(2)->format('Y-m-d H:i'),
        ]);

        $cal = $this->getJson('/api/v1/content-planner/calendar?mode=week');
        $cal->assertOk();
        $titles = collect($cal->json('data.days'))->flatMap(fn ($d) => collect($d['items'])->pluck('title'));
        $this->assertTrue($titles->contains('این هفته'));
        $this->assertFalse($titles->contains('ماه بعد'));
    }

    public function test_move_day_keeps_time(): void
    {
        $svc = app(ContentPlannerService::class);
        $dates = app(DateTimeService::class);
        $content = $svc->create($this->manager, [
            'title' => 'جابجایی',
            'content_type' => 'post',
            'platforms' => ['instagram'],
            'status' => 'scheduled',
            'scheduled_local' => $dates->nowTehran()->addDays(2)->setTime(21, 0)->format('Y-m-d H:i'),
        ]);
        $newDay = $dates->nowTehran()->addDays(4)->toDateString();
        $moved = $svc->moveDay($this->manager, $content->id, $newDay);
        $tehran = $dates->utcToTehran($moved->scheduled_at_utc);
        $this->assertSame('21:00', $tehran->format('H:i'));
        $this->assertSame($newDay, $tehran->toDateString());
    }

    public function test_scheduler_command_runs(): void
    {
        $this->artisan('content-planner:process-reminders')->assertSuccessful();
    }

    public function test_unauthorized_guest(): void
    {
        $this->getJson('/api/v1/content-planner')->assertUnauthorized();
    }
}

class FakeSmsProvider implements SmsProviderInterface
{
    public int $sentCount = 0;

    public int $failTimes = 0;

    public function sendSms(string $mobile, string $message): array
    {
        if ($this->failTimes > 0) {
            $this->failTimes--;

            return ['ok' => false, 'provider' => 'fake', 'error' => 'fail', 'raw' => null];
        }
        $this->sentCount++;

        return ['ok' => true, 'provider' => 'fake', 'message_id' => 'x', 'raw' => ['ok' => true]];
    }
}
