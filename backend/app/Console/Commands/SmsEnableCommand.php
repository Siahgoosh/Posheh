<?php

namespace App\Console\Commands;

use App\Services\Settings\SystemSettingsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class SmsEnableCommand extends Command
{
    protected $signature = 'system:sms-enable
                            {--live : Force sms_mode=live (real SMS)}
                            {--log : Force sms_mode=log (OTP code 123456, no SMS)}
                            {--fix : One-shot fix: live + edge + sync .env + clear cache}
                            {--from-env : Sync SMS credentials from .env into database}
                            {--show : Only print current SMS status}';

    protected $description = 'Enable live SMS / test log mode / sync MaxSMS settings';

    public function handle(SystemSettingsService $settings): int
    {
        if ($this->option('show')) {
            $this->printStatus($settings);

            return self::SUCCESS;
        }

        if ($this->option('fix')) {
            $this->syncFromEnv($settings);
            $settings->set('sms_mode', 'live');
            $apiMode = strtolower(trim((string) env('IPPANEL_API_MODE', '')));
            if ($apiMode === '') {
                $apiMode = $this->hasPanelCredentialsInEnv() ? 'jspd' : 'auto';
                $settings->set('ippanel_api_mode', $apiMode);
            }
            Cache::forget('system_settings');
            $this->info("SMS fix applied: sms_mode=live, ippanel_api_mode={$apiMode}");
            $this->newLine();
            $this->printStatus($settings);
            $this->newLine();
            $this->line('Test: php artisan system:sms-test 09170577873 --otp --debug');
            $this->line('Probe: php artisan system:sms-probe 09170577873 --send');

            return self::SUCCESS;
        }

        if ($this->option('from-env')) {
            $this->syncFromEnv($settings);
        }

        if ($this->option('log')) {
            $settings->set('sms_mode', 'log');
            $this->info('sms_mode set to log — OTP test code: 123456');
        } elseif ($this->option('live')) {
            $settings->set('sms_mode', 'live');
            if (! env('IPPANEL_API_MODE') && ! $settings->hasValue('ippanel_api_mode')) {
                $settings->set('ippanel_api_mode', $this->hasPanelCredentialsInEnv() ? 'jspd' : 'auto');
            }
            $this->info('sms_mode set to live');
        }

        Cache::forget('system_settings');
        $this->newLine();
        $this->printStatus($settings);

        return self::SUCCESS;
    }

    private function syncFromEnv(SystemSettingsService $settings): void
    {
        $map = [
            'SMS_MODE' => 'sms_mode',
            'SMS_PROVIDER' => 'sms_provider',
            'IPPANEL_API_MODE' => 'ippanel_api_mode',
            'IPPANEL_API_KEY' => 'ippanel_api_key',
            'IPPANEL_USERNAME' => 'ippanel_username',
            'IPPANEL_PASSWORD' => 'ippanel_password',
            'IPPANEL_FROM_NUMBER' => 'ippanel_from_number',
            'IPPANEL_OTP_FROM_NUMBER' => 'ippanel_otp_from_number',
            'IPPANEL_OTP_PATTERN_CODE' => 'ippanel_otp_pattern_code',
            'IPPANEL_BASE_URL' => 'ippanel_base_url',
            'SMS_RELAY_URL' => 'sms_relay_url',
            'SMS_RELAY_SECRET' => 'sms_relay_secret',
            'IPPANEL_HTTP_PROXY' => 'ippanel_http_proxy',
        ];

        foreach ($map as $envKey => $settingKey) {
            $value = env($envKey);
            if ($value === null || $value === '') {
                continue;
            }

            if ($settingKey === 'sms_provider') {
                $value = trim((string) $value);
                if (str_contains($value, '=')) {
                    $this->warn("  skipped invalid sms_provider from .env: {$value}");

                    continue;
                }
            }

            if ($settings->set($settingKey, $value)) {
                $this->line("  synced {$settingKey} from .env");
            }
        }

        if (! env('IPPANEL_API_MODE') && ! $settings->hasValue('ippanel_api_mode')) {
            $settings->set('ippanel_api_mode', $this->hasPanelCredentialsInEnv() ? 'jspd' : 'auto');
            $this->line('  set ippanel_api_mode='.($this->hasPanelCredentialsInEnv() ? 'jspd' : 'auto'));
        }

        if (! env('sms_provider') && ! $settings->hasValue('sms_provider')) {
            $settings->set('sms_provider', 'maxsms');
            $this->line('  set sms_provider=maxsms');
        }

        if (! env('IPPANEL_OTP_PATTERN_CODE') && ! $settings->hasValue('ippanel_otp_pattern_code')) {
            $settings->set('ippanel_otp_pattern_code', 'qhhly1nai3njev0');
            $this->line('  set ippanel_otp_pattern_code=qhhly1nai3njev0');
        }

        if (! env('IPPANEL_OTP_FROM_NUMBER') && ! $settings->hasValue('ippanel_otp_from_number')) {
            $settings->set('ippanel_otp_from_number', '+9810008721297974');
            $this->line('  set ippanel_otp_from_number=+9810008721297974');
        }
    }

    private function hasPanelCredentialsInEnv(): bool
    {
        $username = trim((string) env('IPPANEL_USERNAME', ''));
        $password = trim((string) env('IPPANEL_PASSWORD', ''));

        return $username !== '' && $password !== '';
    }

    private function printStatus(SystemSettingsService $settings): void
    {
        $status = $settings->smsStatus();

        $this->table(
            ['Setting', 'Value'],
            [
                ['sms_mode (DB)', (string) $settings->get('sms_mode', '—')],
                ['is_live (effective)', $status['is_live'] ? 'YES' : 'NO'],
                ['has_username', $status['has_username'] ? 'yes' : 'no'],
                ['has_password', $status['has_password'] ? 'yes' : 'no'],
                ['has_api_key', $status['has_api_key'] ? 'yes' : 'no'],
                ['from_number', (string) $settings->get('ippanel_from_number', '—')],
                ['otp_from_number', (string) $settings->get('ippanel_otp_from_number', '—')],
                ['sms_provider', (string) $settings->get('sms_provider', '—')],
                ['api_mode', (string) $settings->get('ippanel_api_mode', '—')],
                ['otp_pattern', (string) $settings->get('ippanel_otp_pattern_code', '—')],
                ['is_ready', $status['is_ready'] ? 'YES' : 'NO'],
                ['SMS_MODE (.env)', (string) env('SMS_MODE', '—')],
            ]
        );
    }
}
