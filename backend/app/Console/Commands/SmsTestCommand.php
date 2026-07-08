<?php

namespace App\Console\Commands;

use App\Services\Settings\SystemSettingsService;
use App\Services\Sms\IpPanelSmsService;
use Illuminate\Console\Command;

class SmsTestCommand extends Command
{
    protected $signature = 'system:sms-test {mobile : Mobile number e.g. 09170577873} {--otp : Send OTP-style message}';

    protected $description = 'Send a test SMS from the server CLI (no admin panel needed)';

    public function handle(SystemSettingsService $settings, IpPanelSmsService $sms): int
    {
        $mobile = $this->argument('mobile');
        $status = $settings->smsStatus();

        $this->table(['Key', 'Value'], collect($status)->map(fn ($v, $k) => [$k, is_bool($v) ? ($v ? 'true' : 'false') : (string) $v])->values()->all());

        if (! $status['is_ready']) {
            $this->error('SMS not ready. Run: php artisan system:sms-enable --live --from-env');

            return self::FAILURE;
        }

        if ($this->option('otp')) {
            $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $this->info("Sending OTP-style message with code: {$code}");
            $result = $sms->sendOtp($mobile, $code);
            $ok = (bool) ($result['success'] ?? false);
            $this->line($ok ? '<info>OTP SMS sent successfully</info>' : '<error>OTP SMS failed — '.($result['message'] ?? 'check storage/logs/laravel.log').'</error>');

            return $ok ? self::SUCCESS : self::FAILURE;
        }

        $result = $sms->test($mobile, 'تست CLI پوشه — '.now()->format('H:i:s'));
        $this->line($result['success'] ? "<info>{$result['message']}</info>" : "<error>{$result['message']}</error>");
        if (! empty($result['method'])) {
            $this->line("Method: {$result['method']}");
        }

        return $result['success'] ? self::SUCCESS : self::FAILURE;
    }
}
