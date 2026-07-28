<?php

namespace App\Console\Commands;

use App\Services\Settings\SystemSettingsService;
use App\Services\Sms\IpPanelSmsService;
use Illuminate\Console\Command;

class OtpSendSmsCommand extends Command
{
    protected $signature = 'otp:send-sms {mobile : Mobile e.g. 09170577873} {code : 6-digit OTP code}';

    protected $description = 'Send OTP SMS (background worker — do not call from web directly)';

    public function handle(SystemSettingsService $settings, IpPanelSmsService $sms): int
    {
        $mobile = (string) $this->argument('mobile');
        $code = (string) $this->argument('code');
        $log = storage_path('logs/otp-sms.log');

        $this->appendLog($log, 'cli_start', [
            'mobile' => $this->mask($mobile),
            'live' => $settings->isSmsLive(),
        ]);

        if (! $settings->isSmsLive()) {
            $this->appendLog($log, 'skipped_log_mode', ['code' => $code]);
            $this->warn('sms_mode is log — no SMS sent. Use system:sms-enable --live first.');

            return self::SUCCESS;
        }

        $status = $settings->smsStatus();
        if (! ($status['is_ready'] ?? false)) {
            $this->appendLog($log, 'credentials_missing', $status);
            $this->error('SMS credentials missing. Run: php artisan system:sms-enable --live --from-env');

            return self::FAILURE;
        }

        $result = $sms->sendOtp($mobile, $code);
        $this->appendLog($log, 'provider_result', $result);

        if ($result['success'] ?? false) {
            $this->info('OTP SMS sent via '.($result['method'] ?? 'unknown'));

            return self::SUCCESS;
        }

        $this->error('OTP SMS failed: '.($result['message'] ?? 'unknown error'));

        return self::FAILURE;
    }

    /** @param array<string, mixed> $data */
    private function appendLog(string $log, string $event, array $data = []): void
    {
        $line = sprintf(
            "[%s] %s\n",
            now()->toIso8601String(),
            json_encode(array_merge(['event' => $event], $data), JSON_UNESCAPED_UNICODE)
        );
        @file_put_contents($log, $line, FILE_APPEND | LOCK_EX);
    }

    private function mask(string $mobile): string
    {
        return substr($mobile, 0, 4).'***'.substr($mobile, -2);
    }
}
