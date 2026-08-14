<?php

namespace Tests\Unit\ContentPlanner;

use App\Services\ContentPlanner\DateTimeService;
use Carbon\CarbonImmutable;
use Tests\TestCase;

class DateTimeServiceTest extends TestCase
{
    private DateTimeService $svc;

    protected function setUp(): void
    {
        parent::setUp();
        $this->svc = new DateTimeService;
        config(['app.timezone' => 'UTC']);
        config(['content_planner.timezone' => 'Asia/Tehran']);
    }

    public function test_tehran_local_to_utc_and_back(): void
    {
        $utc = $this->svc->tehranLocalToUtc('2026-08-19 18:30');
        $tehran = $this->svc->utcToTehran($utc);

        $this->assertSame('18:30', $tehran->format('H:i'));
        $this->assertSame('Asia/Tehran', $tehran->timezoneName);
        $this->assertSame('UTC', $utc->timezoneName);
    }

    public function test_jalali_to_gregorian_roundtrip_labels(): void
    {
        // 28 Mordad 1405 — verify conversion is stable
        $utc = $this->svc->jalaliDateTimeToUtc('1405/05/28', '18:30');
        $tehran = $this->svc->utcToTehran($utc);
        $this->assertSame('18:30', $tehran->format('H:i'));
        $jalali = $this->svc->formatJalaliDate($tehran);
        $this->assertSame('1405/05/28', $jalali);
    }

    public function test_browser_europe_istanbul_does_not_affect_storage(): void
    {
        // Simulate server TZ = Istanbul
        date_default_timezone_set('Europe/Istanbul');
        config(['app.timezone' => 'Europe/Istanbul']);

        $utc = $this->svc->jalaliDateTimeToUtc('1405/05/28', '18:30');
        $tehran = $this->svc->utcToTehran($utc);

        $this->assertSame('18:30', $tehran->format('H:i'));
        $this->assertSame('Asia/Tehran', $tehran->timezoneName);

        date_default_timezone_set('UTC');
        config(['app.timezone' => 'UTC']);
    }

    public function test_reminder_offset_30_minutes_before_is_1800_tehran(): void
    {
        $publish = $this->svc->tehranLocalToUtc('2026-08-19 18:30');
        $reminder = $publish->subMinutes(30);
        $tehran = $this->svc->utcToTehran($reminder);
        $this->assertSame('18:00', $tehran->format('H:i'));
    }

    public function test_boundary_midnight_and_end_of_day(): void
    {
        $a = $this->svc->tehranLocalToUtc('2026-03-20 00:00');
        $b = $this->svc->tehranLocalToUtc('2026-03-20 23:59');
        $this->assertSame('00:00', $this->svc->utcToTehran($a)->format('H:i'));
        $this->assertSame('23:59', $this->svc->utcToTehran($b)->format('H:i'));
    }

    public function test_persian_week_starts_saturday(): void
    {
        // A known Friday in Tehran
        $friday = CarbonImmutable::parse('2026-08-14 12:00', 'Asia/Tehran');
        $this->assertTrue($this->svc->isFriday($friday));
        $start = $this->svc->startOfWeekTehran($friday);
        $this->assertSame('شنبه', $this->svc->weekdayFa($start));
    }

    public function test_now_tehran_uses_config_timezone(): void
    {
        $now = $this->svc->nowTehran();
        $this->assertSame('Asia/Tehran', $now->timezoneName);
    }
}
