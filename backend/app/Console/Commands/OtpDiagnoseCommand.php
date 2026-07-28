<?php

namespace App\Console\Commands;

use App\Services\Settings\SystemSettingsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;

class OtpDiagnoseCommand extends Command
{
    protected $signature = 'otp:diagnose {mobile? : Optional mobile to check recent OTP rows}';

    protected $description = 'Diagnose OTP + SMS pipeline (mode, credentials, queue, logs)';

    public function handle(SystemSettingsService $settings): int
    {
        $status = $settings->smsStatus();
        $config = $settings->ippanelConfig();
        $logFile = storage_path('logs/otp-sms.log');

        $this->info('=== OTP / SMS diagnosis ===');
        $this->table(['Key', 'Value'], [
            ['sms_mode (DB)', (string) $settings->get('sms_mode', '—')],
            ['SMS_MODE (.env)', (string) env('SMS_MODE', '—')],
            ['is_live', ($status['is_live'] ?? false) ? 'YES' : 'NO'],
            ['credentials ready', ($status['is_ready'] ?? false) ? 'YES' : 'NO'],
            ['otp_pattern_code', (string) ($config['otp_pattern_code'] ?? '—')],
            ['from_number', (string) ($config['from_number'] ?? '—')],
            ['otp_from_number', (string) ($config['otp_from_number'] ?? '—')],
            ['api_mode', (string) ($config['api_mode'] ?? '—')],
            ['queue.default', (string) config('queue.default')],
            ['exec enabled', function_exists('exec') && ! in_array('exec', array_map('trim', explode(',', (string) ini_get('disable_functions'))), true) ? 'yes' : 'NO'],
            ['LOG_LEVEL', (string) env('LOG_LEVEL', 'debug')],
            ['otp-sms.log', is_file($logFile) ? 'exists ('.filesize($logFile).' bytes)' : 'missing'],
        ]);

        $this->line('Server outbound IP (whitelist this in MaxSMS panel): '.$this->detectServerIp());

        try {
            $ping = Redis::connection()->ping();
            $this->line('Redis: '.(is_string($ping) ? $ping : 'PONG'));
        } catch (\Throwable $e) {
            $this->warn('Redis: FAIL — '.$e->getMessage());
        }

        $this->newLine();
        $this->info('IPPanel connectivity:');
        foreach (['https://ippanel.com', 'https://edge.ippanel.com'] as $url) {
            try {
                $start = microtime(true);
                $response = Http::connectTimeout(5)->timeout(8)->get($url);
                $ms = (int) round((microtime(true) - $start) * 1000);
                $this->line("  {$url} → HTTP {$response->status()} ({$ms}ms)");
            } catch (\Throwable $e) {
                $this->error("  {$url} → FAIL: ".$e->getMessage());
            }
        }

        if (is_file($logFile)) {
            $this->newLine();
            $this->info('Last 15 lines of storage/logs/otp-sms.log:');
            $lines = array_slice(file($logFile, FILE_IGNORE_NEW_LINES) ?: [], -15);
            foreach ($lines as $line) {
                $this->line($line);
            }
        } else {
            $this->warn('No otp-sms.log yet — SMS background process never ran.');
        }

        $mobile = $this->argument('mobile');
        if ($mobile) {
            $this->call('otp:debug', ['mobile' => $mobile, '--reveal' => true]);
        }

        $this->newLine();
        $this->line('Test SMS directly:  php artisan otp:send-sms 09170577873 123456');
        $this->line('Test via IPPanel:   php artisan system:sms-test 09170577873 --otp --debug');
        $this->line('Enable live SMS:    php artisan system:sms-enable --live --from-env');

        return self::SUCCESS;
    }

    private function detectServerIp(): string
    {
        try {
            $ip = trim((string) Http::timeout(5)->get('https://api.ipify.org')->body());
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        } catch (\Throwable) {
            // fall through
        }

        return 'unknown (run: curl -s https://api.ipify.org)';
    }
}
