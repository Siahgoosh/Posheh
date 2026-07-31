<?php

namespace App\Console\Commands;

use App\Services\Settings\SystemSettingsService;
use App\Services\Sms\IpPanelSmsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class SmsProbeCommand extends Command
{
    protected $signature = 'system:sms-probe
                            {mobile? : Mobile to send test SMS e.g. 09170577873}
                            {--send : Actually send test OTP + webservice SMS}
                            {--otp-only : Only test OTP pattern}
                            {--plain-only : Only test webservice plain SMS}';

    protected $description = 'Full SMS pipeline probe: config, Edge connectivity, auth, optional test send';

    public function handle(SystemSettingsService $settings, IpPanelSmsService $sms): int
    {
        $config = $settings->ippanelConfig();
        $status = $settings->smsStatus();

        $this->info('=== SMS Pipeline Probe ===');
        $this->newLine();

        $this->table(['Check', 'Value'], [
            ['sms_mode (DB)', (string) $settings->get('sms_mode', '—')],
            ['SMS_MODE (.env)', (string) env('SMS_MODE', '—')],
            ['is_live (effective)', ($status['is_live'] ?? false) ? 'YES ✓' : 'NO ✗ — SMS disabled!'],
            ['api_mode', (string) ($config['api_mode'] ?? '—')],
            ['has_api_key', ! empty($config['api_key']) ? 'yes ('.strlen((string) $config['api_key']).' chars)' : 'NO ✗'],
            ['has_username', ($status['has_username'] ?? false) ? 'yes' : 'no'],
            ['from_number', (string) ($config['from_number'] ?? '—')],
            ['otp_from_number', (string) ($config['otp_from_number'] ?? '—')],
            ['otp_pattern', (string) ($config['otp_pattern_code'] ?? '—')],
            ['base_url', (string) ($config['base_url'] ?? '—')],
            ['sms_relay', ! empty($config['relay_url']) ? 'YES ✓ '.($config['relay_url']) : 'NO — required for NL server'],
            ['http_proxy', ! empty($config['http_proxy']) ? 'yes' : 'no'],
            ['queue', (string) config('queue.default')],
        ]);

        if (! ($status['is_live'] ?? false)) {
            $this->newLine();
            $this->error('ROOT CAUSE: sms_mode is LOG — no real SMS is sent.');
            $this->line('Fix: php artisan system:sms-enable --live --from-env');
            $this->line('Note: deploy.sh used to force --log on every deploy (fixed in latest version).');
        }

        if (empty($config['api_key']) && ($config['api_mode'] ?? '') === 'edge') {
            $this->newLine();
            $this->error('ROOT CAUSE: IPPANEL_API_KEY is empty but api_mode=edge.');
            $this->line('Fix: add IPPANEL_API_KEY to .env from panel → Developers → Access Keys');
        }

        $this->newLine();
        $this->info('Edge API connectivity:');
        $baseUrl = rtrim((string) ($config['base_url'] ?? 'https://edge.ippanel.com/v1'), '/');
        $sendUrl = str_ends_with($baseUrl, '/api') ? "{$baseUrl}/send" : "{$baseUrl}/api/send";

        $edgeReachable = false;
        foreach ([$baseUrl, $sendUrl] as $url) {
            try {
                $start = microtime(true);
                $response = $this->probeHttp($config)->get($url);
                $ms = (int) round((microtime(true) - $start) * 1000);
                $icon = $response->successful() || $response->status() === 401 || $response->status() === 405 ? '✓' : '✗';
                $this->line("  {$icon} {$url} → HTTP {$response->status()} ({$ms}ms)");
                if ($response->status() > 0) {
                    $edgeReachable = true;
                }
            } catch (\Throwable $e) {
                $this->error("  ✗ {$url} → FAIL: ".$e->getMessage());
            }
        }

        if (! $edgeReachable && empty($config['relay_url'])) {
            $this->newLine();
            $this->error('ROOT CAUSE: سرور به edge.ippanel.com دسترسی شبکه ندارد (timeout).');
            $this->line('این مشکل whitelist نیست — مسیر شبکه هلند→ایران بسته است.');
            $this->line('راه‌حل: SMS Relay روی سرور ایران — docs/SMS-RELAY.md');
            $this->line('  1) scripts/sms-relay/relay.php را روی VPS ایران deploy کنید');
            $this->line('  2) در .env هلند: SMS_RELAY_URL و SMS_RELAY_SECRET');
            $this->line('  3) ./scripts/fix-sms-now.sh && system:sms-probe --send');
        }

        if (! empty($config['relay_url'])) {
            $this->newLine();
            $this->info('SMS Relay configured: '.$config['relay_url']);
            try {
                $ping = Http::connectTimeout(5)->timeout(10)->get($config['relay_url']);
                $this->line('  Relay HTTP status: '.$ping->status().' (405/403 expected for GET)');
            } catch (\Throwable $e) {
                $this->error('  Relay unreachable: '.$e->getMessage());
            }
        }

        if (! empty($config['api_key']) && $edgeReachable) {
            $this->newLine();
            $this->info('Auth probe (invalid payload — checks if API key is accepted):');
            try {
                $response = $this->probeHttp($config)
                    ->withHeaders([
                        'Authorization' => (string) $config['api_key'],
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json',
                    ])
                    ->post($sendUrl, [
                        'sending_type' => 'pattern',
                        'from_number' => $config['otp_from_number'] ?? '+9810008721297974',
                        'code' => $config['otp_pattern_code'] ?? 'qhhly1nai3njev0',
                        'recipients' => ['+989000000000'],
                        'params' => ['code' => '000000'],
                    ]);

                $body = $response->json();
                $metaMsg = $body['meta']['message'] ?? $response->body();
                $this->line("  HTTP {$response->status()}: ".mb_substr((string) $metaMsg, 0, 120));

                if ($response->status() === 401) {
                    $this->error('  ✗ API key rejected (401). Update IPPANEL_API_KEY.');
                } elseif ($response->status() === 502) {
                    $this->error('  ✗ Edge API returns 502 — service down or IP blocked.');
                } elseif ($response->status() === 422) {
                    $this->info('  ✓ API key accepted (422 = validation error on test number, expected)');
                } elseif ($response->successful() && ($body['meta']['status'] ?? false)) {
                    $this->info('  ✓ API key works and request accepted');
                }
            } catch (\Throwable $e) {
                $this->error('  Auth probe failed: '.$e->getMessage());
            }
        }

        $mobile = $this->argument('mobile');
        if ($this->option('send') && $mobile) {
            if (! ($status['is_live'] ?? false)) {
                $this->error('Cannot send: sms_mode is not live. Run system:sms-enable --live first.');

                return self::FAILURE;
            }

            $this->newLine();
            $this->info("Sending test SMS to {$mobile}...");

            if (! $this->option('plain-only')) {
                $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                $this->line("OTP pattern test with code: {$code}");
                $otpResult = $sms->sendOtp($mobile, $code);
                $this->printResult('OTP', $otpResult);
            }

            if (! $this->option('otp-only')) {
                $plainResult = $sms->test($mobile, 'تست پوشه — '.now()->format('H:i:s'));
                $this->printResult('Webservice', $plainResult);
            }
        } elseif ($this->option('send')) {
            $this->warn('Provide mobile: php artisan system:sms-probe 09170577873 --send');
        } else {
            $this->newLine();
            $this->line('To send real test SMS:');
            $this->line('  php artisan system:sms-probe 09170577873 --send');
        }

        $logFile = storage_path('logs/otp-sms.log');
        if (is_file($logFile)) {
            $this->newLine();
            $this->info('Last 5 lines of otp-sms.log:');
            foreach (array_slice(file($logFile, FILE_IGNORE_NEW_LINES) ?: [], -5) as $line) {
                $this->line('  '.$line);
            }
        }

        return ($status['is_live'] ?? false) && (! empty($config['relay_url']) || ! empty($config['api_key'])) ? self::SUCCESS : self::FAILURE;
    }

    /** @param array<string, mixed> $config */
    private function probeHttp(array $config): \Illuminate\Http\Client\PendingRequest
    {
        $request = Http::connectTimeout(8)->timeout(12);
        $proxy = trim((string) ($config['http_proxy'] ?? ''));

        if ($proxy !== '') {
            $request = $request->withOptions(['proxy' => $proxy]);
        }

        return $request;
    }

    /** @param array<string, mixed> $result */
    private function printResult(string $label, array $result): void
    {
        if ($result['success'] ?? false) {
            $this->info("  ✓ {$label} sent via ".($result['method'] ?? 'unknown'));
        } else {
            $this->error("  ✗ {$label} failed: ".($result['message'] ?? 'unknown'));
            if (! empty($result['details'])) {
                $this->line('    details: '.json_encode($result['details'], JSON_UNESCAPED_UNICODE));
            }
        }
    }
}
