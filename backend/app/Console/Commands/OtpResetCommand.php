<?php

namespace App\Console\Commands;

use App\Models\OtpCode;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class OtpResetCommand extends Command
{
    protected $signature = 'otp:reset {mobile : Mobile number e.g. 09170577873}';

    protected $description = 'Clear OTP send cooldown and cached code for a mobile';

    public function handle(): int
    {
        $mobile = $this->normalizeMobile((string) $this->argument('mobile'));

        Cache::forget('otp_rate:'.$mobile);
        Cache::forget('otp_active:'.$mobile);

        $this->info("Cleared OTP cooldown/cache for {$mobile}");
        $this->line('User can request a new code immediately from the website.');

        $active = OtpCode::query()
            ->whereIn('mobile', $this->mobileVariants($mobile))
            ->whereNull('verified_at')
            ->where('expires_at', '>', now())
            ->count();

        if ($active > 0) {
            $this->line("Active OTP rows in DB: {$active} (still valid until expiry).");
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
}
