<?php

namespace App\Console\Commands;

use App\Services\Settings\SystemSettingsService;
use App\Services\Sms\IpPanelSmsService;
use Illuminate\Console\Command;

class SmsTestCommand extends Command
{
    protected $signature = 'system:sms-test {mobile : Mobile number e.g. 09170577873} {--otp : Send OTP-style message} {--debug : Show Edge/JSPD diagnostic details}';

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

            $config = $settings->ippanelConfig();
            $this->line('Pattern: '.($config['otp_pattern_code'] ?? '—'));
            $this->line('From: '.($config['otp_from_number'] ?? $config['from_number'] ?? '—'));
            $this->line('API mode: '.($config['api_mode'] ?? '—'));
            $this->line('Has API key: '.(! empty($config['api_key']) ? 'yes' : 'no'));

            if ($this->option('debug')) {
                $this->warn('OTP path order: 1) classic pattern  2) JSPD  3) Edge API (if api key set)');
                $this->line('OTP from: '.($config['otp_from_number'] ?? $config['from_number'] ?? '—'));
                $this->line('Base URL: '.($config['base_url'] ?? '—'));
                $this->line('Username set: '.(! empty($config['username']) ? 'yes' : 'NO — required for OTP'));
                $this->line('Password set: '.(! empty($config['password']) ? 'yes' : 'NO — required for OTP'));
            }

            $result = $sms->sendOtp($mobile, $code);
            $ok = (bool) ($result['success'] ?? false);

            if ($ok) {
                $this->info('OTP SMS sent successfully');
                if (! empty($result['method'])) {
                    $this->line("Method: {$result['method']}");
                }
            } else {
                $this->error('OTP SMS failed');
                $this->line('Reason: '.($result['message'] ?? 'check storage/logs/laravel.log'));
                if (! empty($result['method'])) {
                    $this->line("Last method: {$result['method']}");
                }
                if (! empty($result['details']['raw'])) {
                    $this->line('Provider raw: '.mb_substr((string) $result['details']['raw'], 0, 200));
                }
                if (! empty($result['details']['code'])) {
                    $this->line('Provider code: '.$result['details']['code']);
                }
                if ($this->option('debug')) {
                    $this->newLine();
                    if (! empty($result['attempts']) && is_array($result['attempts'])) {
                        $this->warn('Attempts:');
                        foreach ($result['attempts'] as $i => $attempt) {
                            $n = $i + 1;
                            $method = $attempt['method'] ?? '—';
                            $msg = $attempt['message'] ?? '—';
                            $this->line("  {$n}. [{$method}] {$msg}");
                        }
                    }
                    $this->warn('Classic pattern needs IPPANEL_USERNAME + IPPANEL_PASSWORD in .env');
                    $this->line('JSPD deny = whitelist server IP in MaxSMS panel');
                }
            }

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
