<?php

namespace App\Console\Commands;

use App\Services\Settings\SystemSettingsService;
use App\Services\Sms\IpPanelSmsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class SmsApiTestCommand extends Command
{
    protected $signature = 'system:sms-api-test
                            {mobile : Mobile e.g. 09170577873}
                            {--send : Actually send test SMS via each working API path}';

    protected $description = 'Test MaxSMS/IPPanel API paths: Edge webservice vs JSPD webservice — pick what works from this server';

    public function handle(SystemSettingsService $settings, IpPanelSmsService $sms): int
    {
        $config = $settings->ippanelConfig();
        $mobile = (string) $this->argument('mobile');
        $results = [];

        $this->info('=== SMS API Test (from this server) ===');
        $this->line('Server egress IP: '.$this->detectEgressIp());
        $this->newLine();

        $results['edge_connectivity'] = $this->testEdgeConnectivity($config);
        $results['jspd_connectivity'] = $this->testJspdConnectivity($config);
        $results['edge_webservice'] = $this->testEdgeWebserviceApi($config);
        $results['jspd_webservice'] = $this->testJspdWebserviceApi($config);

        $this->newLine();
        $this->info('=== Summary ===');
        $this->table(
            ['Path', 'Status', 'Detail'],
            collect($results)->map(fn ($r, $k) => [
                $k,
                ($r['ok'] ?? false) ? '✓ OK' : '✗ FAIL',
                $r['detail'] ?? '—',
            ])->values()->all()
        );

        $webserviceOk = ($results['jspd_webservice']['ok'] ?? false)
            || ($results['jspd_connectivity']['ok'] ?? false);
        $edgeOk = ($results['edge_webservice']['ok'] ?? false);

        $this->newLine();
        if ($webserviceOk) {
            $this->info('✓ RECOMMENDED: JSPD webservice API (IPPANEL_API_MODE=jspd)');
            $this->line('  OTP and SMS via ippanel.com/services.jspd — no pattern, no Edge.');
        } elseif ($edgeOk) {
            $this->info('✓ RECOMMENDED: Edge API webservice (IPPANEL_API_MODE=edge + API key)');
        } else {
            $this->error('✗ No working API path from this server.');
            $this->line('  Check IPPANEL_USERNAME/PASSWORD or IPPANEL_API_KEY in .env');
            $this->line('  Edge often returns 502 from abroad; JSPD webservice is the NL path.');
        }

        if ($this->option('send')) {
            if (! $settings->isSmsLive()) {
                $this->error('sms_mode is not live. Run: php artisan system:sms-enable --live');

                return self::FAILURE;
            }

            $this->newLine();
            $this->info("Sending real test SMS to {$mobile}...");

            $plain = $sms->test($mobile, 'تست API وب‌سرویس — '.now()->format('H:i:s'));
            $this->printSendResult('Webservice plain', $plain);

            $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $otp = $sms->sendOtp($mobile, $code);
            $this->printSendResult("OTP webservice (code {$code})", $otp);
        } else {
            $this->newLine();
            $this->line('To send real SMS: php artisan system:sms-api-test '.$mobile.' --send');
        }

        return $webserviceOk || $edgeOk ? self::SUCCESS : self::FAILURE;
    }

    /** @param array<string, mixed> $config */
    private function testEdgeConnectivity(array $config): array
    {
        $baseUrl = rtrim((string) ($config['base_url'] ?? 'https://edge.ippanel.com/v1'), '/');

        try {
            $start = microtime(true);
            $response = $this->http($config)->get($baseUrl);
            $ms = (int) round((microtime(true) - $start) * 1000);
            $ok = in_array($response->status(), [200, 401, 405], true);

            $this->line("Edge connectivity: HTTP {$response->status()} ({$ms}ms)");

            return [
                'ok' => $ok && $response->status() !== 502,
                'detail' => "HTTP {$response->status()} ({$ms}ms)",
            ];
        } catch (\Throwable $e) {
            $this->error('Edge connectivity: '.$e->getMessage());

            return ['ok' => false, 'detail' => $e->getMessage()];
        }
    }

    /** @param array<string, mixed> $config */
    private function testJspdConnectivity(array $config): array
    {
        if (empty($config['username']) || empty($config['password'])) {
            $this->warn('JSPD connectivity: skipped (no username/password)');

            return ['ok' => false, 'detail' => 'no panel credentials'];
        }

        try {
            $start = microtime(true);
            $response = $this->http($config)
                ->asForm()
                ->post('https://ippanel.com/services.jspd', [
                    'uname' => (string) $config['username'],
                    'pass' => (string) $config['password'],
                    'op' => 'getcredit',
                ]);
            $ms = (int) round((microtime(true) - $start) * 1000);
            $raw = trim($response->body());
            $ok = $raw !== '' && strcasecmp($raw, 'deny') !== 0;

            $this->line("JSPD getcredit: HTTP {$response->status()} ({$ms}ms) → ".mb_substr($raw, 0, 60));

            return [
                'ok' => $ok,
                'detail' => $ok ? "credit check OK ({$ms}ms)" : ($raw === 'deny' ? 'deny' : 'empty'),
            ];
        } catch (\Throwable $e) {
            $this->error('JSPD connectivity: '.$e->getMessage());

            return ['ok' => false, 'detail' => $e->getMessage()];
        }
    }

    /** @param array<string, mixed> $config */
    private function testEdgeWebserviceApi(array $config): array
    {
        if (empty($config['api_key'])) {
            $this->warn('Edge webservice API: skipped (no IPPANEL_API_KEY)');

            return ['ok' => false, 'detail' => 'no API key'];
        }

        $baseUrl = rtrim((string) ($config['base_url'] ?? 'https://edge.ippanel.com/v1'), '/');
        $sendUrl = str_ends_with($baseUrl, '/api') ? "{$baseUrl}/send" : "{$baseUrl}/api/send";
        $from = (string) ($config['from_number'] ?? '+983000505');

        try {
            $start = microtime(true);
            $response = $this->http($config)
                ->withHeaders([
                    'Authorization' => (string) $config['api_key'],
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->post($sendUrl, [
                    'sending_type' => 'webservice',
                    'from_number' => $from,
                    'message' => 'API test — do not deliver',
                    'params' => ['recipients' => ['+989000000000']],
                ]);
            $ms = (int) round((microtime(true) - $start) * 1000);
            $body = $response->json();
            $metaMsg = (string) ($body['meta']['message'] ?? $response->body());

            // 422 = API accepted auth but rejected test payload — that's OK for probe
            $ok = $response->status() === 422
                || (($body['meta']['status'] ?? false) && $response->successful());

            $this->line("Edge webservice API: HTTP {$response->status()} ({$ms}ms) → ".mb_substr($metaMsg, 0, 80));

            return [
                'ok' => $ok,
                'detail' => "HTTP {$response->status()}: ".mb_substr($metaMsg, 0, 100),
            ];
        } catch (\Throwable $e) {
            $this->error('Edge webservice API: '.$e->getMessage());

            return ['ok' => false, 'detail' => $e->getMessage()];
        }
    }

    /** @param array<string, mixed> $config */
    private function testJspdWebserviceApi(array $config): array
    {
        if (empty($config['username']) || empty($config['password'])) {
            $this->warn('JSPD webservice API: skipped (no username/password)');

            return ['ok' => false, 'detail' => 'no panel credentials'];
        }

        $from = preg_replace('/\D/', '', (string) ($config['from_number'] ?? ''));
        if (str_starts_with($from, '98')) {
            $from = substr($from, 2);
        }
        $from = ltrim($from, '0');

        $jspdMobile = '9170000000';

        try {
            $start = microtime(true);
            $response = $this->http($config)
                ->asForm()
                ->post('https://ippanel.com/services.jspd', [
                    'uname' => (string) $config['username'],
                    'pass' => (string) $config['password'],
                    'from' => $from,
                    'to' => json_encode([$jspdMobile]),
                    'message' => 'API webservice probe — ignore',
                    'op' => 'send',
                ]);
            $ms = (int) round((microtime(true) - $start) * 1000);
            $raw = trim($response->body());

            // Success = numeric tracking id or JSON [0, msg]
            $ok = preg_match('/^\d+$/', $raw) === 1;
            if (! $ok && str_starts_with($raw, '[')) {
                $decoded = json_decode($raw, true);
                $ok = is_array($decoded) && in_array((string) ($decoded[0] ?? ''), ['0', '1'], true);
            }

            if (strcasecmp($raw, 'deny') === 0) {
                $ok = false;
            }

            $this->line("JSPD webservice API (op=send): HTTP {$response->status()} ({$ms}ms) → ".mb_substr($raw, 0, 80));

            return [
                'ok' => $ok,
                'detail' => $ok ? "send accepted ({$ms}ms)" : mb_substr($raw, 0, 80),
            ];
        } catch (\Throwable $e) {
            $this->error('JSPD webservice API: '.$e->getMessage());

            return ['ok' => false, 'detail' => $e->getMessage()];
        }
    }

    private function detectEgressIp(): string
    {
        try {
            return trim(Http::connectTimeout(3)->timeout(5)->get('https://ifconfig.me')->body()) ?: 'unknown';
        } catch (\Throwable) {
            return 'unknown';
        }
    }

    /** @param array<string, mixed> $config */
    private function http(array $config): \Illuminate\Http\Client\PendingRequest
    {
        $request = Http::connectTimeout(8)->timeout(15);
        $proxy = trim((string) ($config['http_proxy'] ?? ''));

        if ($proxy !== '') {
            $request = $request->withOptions(['proxy' => $proxy]);
        }

        return $request;
    }

    /** @param array<string, mixed> $result */
    private function printSendResult(string $label, array $result): void
    {
        if ($result['success'] ?? false) {
            $this->info("  ✓ {$label} → ".($result['method'] ?? 'ok'));
        } else {
            $this->error("  ✗ {$label} → ".($result['message'] ?? 'failed'));
        }
    }
}
