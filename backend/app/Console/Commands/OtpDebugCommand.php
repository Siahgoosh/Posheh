<?php

namespace App\Console\Commands;

use App\Models\OtpCode;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class OtpDebugCommand extends Command
{
    protected $signature = 'otp:debug {mobile : Mobile number e.g. 09170577873}';

    protected $description = 'Show active OTP state for a mobile (for troubleshooting login)';

    public function handle(): int
    {
        $mobile = $this->normalizeMobile((string) $this->argument('mobile'));
        $cacheKey = 'otp_active:'.$mobile;
        $cached = Cache::get($cacheKey);

        $this->info("Mobile: {$mobile}");
        $this->line('Cache key: '.$cacheKey);
        $this->line('Cached code: '.($cached !== null ? $this->maskCode((string) $cached) : '(empty)'));

        $rows = OtpCode::query()
            ->whereIn('mobile', $this->mobileVariants($mobile))
            ->orderByDesc('id')
            ->limit(5)
            ->get();

        if ($rows->isEmpty()) {
            $this->warn('No OTP rows found in database for this mobile.');

            return self::SUCCESS;
        }

        $this->table(
            ['id', 'mobile', 'code', 'attempts', 'expires_at', 'verified_at', 'created_at'],
            $rows->map(fn (OtpCode $otp) => [
                $otp->id,
                $otp->mobile,
                $this->maskCode($otp->code),
                $otp->attempts,
                $otp->expires_at?->toDateTimeString(),
                $otp->verified_at?->toDateTimeString() ?? '-',
                $otp->created_at?->toDateTimeString(),
            ])->all()
        );

        $active = $rows->first(fn (OtpCode $otp) => $otp->verified_at === null && $otp->expires_at?->isFuture());

        if ($active) {
            $this->info('Latest active OTP: id='.$active->id.', attempts='.$active->attempts.', expires='.$active->expires_at);
        } else {
            $this->warn('No unverified non-expired OTP in the latest rows.');
        }

        return self::SUCCESS;
    }

    private function normalizeMobile(string $mobile): string
    {
        $mobile = preg_replace('/\D/', '', $mobile);

        if (str_starts_with($mobile, '98')) {
            $mobile = '0'.substr($mobile, 2);
        }

        if ($mobile !== '' && ! str_starts_with($mobile, '0')) {
            $mobile = '0'.$mobile;
        }

        return $mobile;
    }

    /** @return list<string> */
    private function mobileVariants(string $mobile): array
    {
        $normalized = $this->normalizeMobile($mobile);

        return array_values(array_unique(array_filter([
            $mobile,
            $normalized,
            ltrim($normalized, '0'),
            '98'.ltrim($normalized, '0'),
        ])));
    }

    private function maskCode(string $code): string
    {
        if (strlen($code) < 4) {
            return '****';
        }

        return substr($code, 0, 2).'**'.substr($code, -2);
    }
}
