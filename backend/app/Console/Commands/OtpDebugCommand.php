<?php

namespace App\Console\Commands;

use App\DTOs\Auth\VerifyOtpDTO;
use App\Models\OtpCode;
use App\Services\Auth\OtpService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class OtpDebugCommand extends Command
{
    protected $signature = 'otp:debug
        {mobile : Mobile number e.g. 09170577873}
        {--code= : Test whether this code would verify}
        {--reveal : Show full OTP codes (server CLI only)}';

    protected $description = 'Show active OTP state for a mobile (for troubleshooting login)';

    public function handle(OtpService $otpService): int
    {
        $mobile = $this->normalizeMobile((string) $this->argument('mobile'));
        $cacheKey = 'otp_active:'.$mobile;
        $cached = Cache::get($cacheKey);
        $reveal = (bool) $this->option('reveal');

        $this->info("Mobile: {$mobile}");
        $this->line('Cache key: '.$cacheKey);
        $this->line('Cached code: '.$this->formatCode($cached, $reveal));

        $rows = OtpCode::query()
            ->whereIn('mobile', $this->mobileVariants($mobile))
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        if ($rows->isEmpty()) {
            $this->warn('No OTP rows found in database for this mobile.');

            return self::SUCCESS;
        }

        $activeRows = $rows->filter(
            fn (OtpCode $otp) => $otp->verified_at === null && $otp->expires_at?->isFuture()
        );

        $this->table(
            ['id', 'mobile', 'code', 'active', 'attempts', 'expires_at', 'verified_at'],
            $rows->map(fn (OtpCode $otp) => [
                $otp->id,
                $otp->mobile,
                $this->formatCode($otp->code, $reveal),
                ($otp->verified_at === null && $otp->expires_at?->isFuture()) ? 'yes' : 'no',
                $otp->attempts,
                $otp->expires_at?->toDateTimeString(),
                $otp->verified_at?->toDateTimeString() ?? '-',
            ])->all()
        );

        $latestActive = $activeRows->first();
        if ($latestActive) {
            $this->info('Latest active OTP: id='.$latestActive->id.', code='.$this->formatCode($latestActive->code, $reveal).', attempts='.$latestActive->attempts);
        } else {
            $this->warn('No unverified non-expired OTP in the latest rows.');
        }

        if ($activeRows->count() > 1) {
            $this->warn('Multiple active OTP rows detected — verify now matches by submitted code, not only the latest row.');
        }

        $testCode = $this->option('code');
        if ($testCode !== null && $testCode !== '') {
            $this->newLine();
            $this->info('Testing verify for code: '.$this->formatCode($testCode, true));

            try {
                $result = $otpService->verify(new VerifyOtpDTO(
                    mobile: $mobile,
                    code: (string) $testCode,
                ));

                $this->info('Verify OK — user #'.$result['user']->id.' '.$result['user']->name);
            } catch (ValidationException $e) {
                $messages = collect($e->errors())->flatten()->implode(' | ');
                $this->error('Verify failed: '.$messages);
            }
        } elseif (! $reveal) {
            $this->line('Tip: add --reveal to see full codes, or --code=123456 to test verify from CLI.');
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

    private function formatCode(mixed $code, bool $reveal): string
    {
        if ($code === null || $code === '') {
            return '(empty)';
        }

        $code = (string) $code;

        if ($reveal) {
            return $code;
        }

        if (strlen($code) < 4) {
            return '****';
        }

        return substr($code, 0, 2).'**'.substr($code, -2);
    }
}
