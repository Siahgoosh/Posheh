<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Forwards SMS requests to a relay script on an Iran-accessible server
 * when the main app (e.g. Netherlands) cannot reach edge.ippanel.com.
 */
class SmsRelayClient
{
    /** @param array<string, mixed> $config */
    public function isConfigured(array $config): bool
    {
        return trim((string) ($config['relay_url'] ?? '')) !== '';
    }

    /** @param array<string, mixed> $config */
    public function sendOtp(string $mobile, string $code, array $config): array
    {
        return $this->post($config, [
            'type' => 'otp',
            'mobile' => $mobile,
            'code' => $code,
            'pattern_code' => $config['otp_pattern_code'] ?? null,
            'from_number' => $config['otp_from_number'] ?? $config['from_number'] ?? null,
        ]);
    }

    /** @param array<string, mixed> $config */
    public function sendPlain(string $mobile, string $message, array $config): array
    {
        return $this->post($config, [
            'type' => 'plain',
            'mobile' => $mobile,
            'message' => $message,
            'from_number' => $config['from_number'] ?? null,
        ]);
    }

    /** @param array<string, mixed> $config */
    /** @param array<string, mixed> $payload */
    private function post(array $config, array $payload): array
    {
        $url = trim((string) ($config['relay_url'] ?? ''));
        $secret = trim((string) ($config['relay_secret'] ?? ''));

        if ($url === '') {
            return ['success' => false, 'message' => 'SMS relay URL not configured'];
        }

        try {
            $response = Http::connectTimeout(8)
                ->timeout(25)
                ->acceptJson()
                ->withHeaders(array_filter([
                    'X-SMS-Relay-Secret' => $secret !== '' ? $secret : null,
                    'Content-Type' => 'application/json',
                ]))
                ->post($url, $payload);

            $body = $response->json() ?? [];

            if ($response->successful() && ($body['success'] ?? false)) {
                return [
                    'success' => true,
                    'message' => $body['message'] ?? 'ارسال شد',
                    'method' => 'sms_relay',
                    'details' => $body['details'] ?? null,
                ];
            }

            $message = $body['message'] ?? "Relay HTTP {$response->status()}";

            Log::error('SMS relay failed', [
                'url' => $url,
                'status' => $response->status(),
                'message' => $message,
            ]);

            return [
                'success' => false,
                'message' => $message,
                'method' => 'sms_relay',
                'details' => $body,
            ];
        } catch (\Throwable $e) {
            Log::error('SMS relay exception', ['url' => $url, 'error' => $e->getMessage()]);

            return [
                'success' => false,
                'message' => 'خطای اتصال به relay SMS: '.$e->getMessage(),
                'method' => 'sms_relay',
            ];
        }
    }
}
