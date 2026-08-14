<?php

namespace App\Services\ContentPlanner;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Morilog\Jalali\Jalalian;

/**
 * Central DateTime service for Content Planner (and reusable elsewhere).
 * Business timezone is always Asia/Tehran — never browser TZ.
 */
class DateTimeService
{
    public const TZ = 'Asia/Tehran';

    public function timezone(): string
    {
        return (string) config('content_planner.timezone', self::TZ);
    }

    public function nowTehran(): CarbonImmutable
    {
        return CarbonImmutable::now($this->timezone());
    }

    public function nowUtc(): CarbonImmutable
    {
        return CarbonImmutable::now('UTC');
    }

    /**
     * Parse Tehran wall-clock local datetime into UTC Carbon.
     * Accepts: "YYYY-MM-DD HH:mm", "YYYY-MM-DDTHH:mm", "YYYY-MM-DD HH:mm:ss"
     */
    public function tehranLocalToUtc(string $local): CarbonImmutable
    {
        $normalized = str_replace('T', ' ', trim($local));
        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $normalized)) {
            $normalized .= ':00';
        }

        return CarbonImmutable::createFromFormat('Y-m-d H:i:s', $normalized, $this->timezone())
            ->setTimezone('UTC');
    }

    public function utcToTehran(Carbon|CarbonImmutable|string|null $utc): ?CarbonImmutable
    {
        if ($utc === null || $utc === '') {
            return null;
        }
        if ($utc instanceof CarbonImmutable || $utc instanceof Carbon) {
            // Treat naive/app-local DB values as UTC wall-clock (we always persist UTC strings).
            $c = CarbonImmutable::parse($utc->format('Y-m-d H:i:s'), 'UTC');
        } else {
            $c = CarbonImmutable::parse((string) $utc, 'UTC');
        }

        return $c->setTimezone($this->timezone());
    }

    public function formatJalali(?CarbonImmutable $tehran, string $format = 'Y/m/d H:i'): ?string
    {
        if (! $tehran) {
            return null;
        }

        return Jalalian::fromCarbon($tehran->toMutable())->format($format);
    }

    public function formatJalaliDate(?CarbonImmutable $tehran): ?string
    {
        return $this->formatJalali($tehran, 'Y/m/d');
    }

    public function formatJalaliTime(?CarbonImmutable $tehran): ?string
    {
        return $this->formatJalali($tehran, 'H:i');
    }

    /** Convert Jalali Y/m/d + H:i (Tehran) → UTC */
    public function jalaliDateTimeToUtc(string $jalaliDate, string $time = '00:00'): CarbonImmutable
    {
        $date = preg_replace('/[^\d\/]/', '', str_replace(['-', '.'], '/', $jalaliDate)) ?? '';
        $time = preg_replace('/[^\d:]/', '', $time) ?: '00:00';
        if (! preg_match('/^\d{1,2}:\d{2}/', $time)) {
            $time = '00:00';
        }
        if (strlen($time) === 5) {
            $time .= ':00';
        }

        [$jy, $jm, $jd] = array_map('intval', explode('/', $date));
        $g = Jalalian::fromFormat('Y/m/d', sprintf('%04d/%02d/%02d', $jy, $jm, $jd))->toCarbon();
        [$h, $i, $s] = array_map('intval', explode(':', $time));

        // Build wall-clock in Asia/Tehran — never use browser/server local offset math.
        return CarbonImmutable::create(
            (int) $g->format('Y'),
            (int) $g->format('m'),
            (int) $g->format('d'),
            $h,
            $i,
            $s ?? 0,
            $this->timezone()
        )->setTimezone('UTC');
    }

    public function startOfWeekTehran(?CarbonImmutable $ref = null): CarbonImmutable
    {
        // Persian week starts Saturday
        $ref = $ref?->setTimezone($this->timezone()) ?? $this->nowTehran();
        $dow = (int) $ref->dayOfWeek; // 0=Sun ... 6=Sat
        $daysFromSaturday = ($dow + 1) % 7;

        return $ref->startOfDay()->subDays($daysFromSaturday);
    }

    public function endOfWeekTehran(?CarbonImmutable $ref = null): CarbonImmutable
    {
        return $this->startOfWeekTehran($ref)->addDays(6)->endOfDay();
    }

    public function isFriday(?CarbonImmutable $ref = null): bool
    {
        $ref = $ref?->setTimezone($this->timezone()) ?? $this->nowTehran();

        return (int) $ref->dayOfWeek === Carbon::FRIDAY;
    }

    public function weekdayFa(CarbonImmutable $tehran): string
    {
        return match ((int) $tehran->dayOfWeek) {
            Carbon::SATURDAY => 'شنبه',
            Carbon::SUNDAY => 'یکشنبه',
            Carbon::MONDAY => 'دوشنبه',
            Carbon::TUESDAY => 'سه‌شنبه',
            Carbon::WEDNESDAY => 'چهارشنبه',
            Carbon::THURSDAY => 'پنجشنبه',
            default => 'جمعه',
        };
    }

    public function dayLabel(CarbonImmutable $tehran): string
    {
        $j = Jalalian::fromCarbon($tehran->toMutable());

        return $this->weekdayFa($tehran).' '.$j->format('d F');
    }
}
