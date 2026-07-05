<?php

namespace App\Services\Sms;

use App\Services\Settings\SystemSettingsService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class IpPanelSmsService
{
    public function __construct(
        private readonly SystemSettingsService $settings,
    ) {}

    public function sendOtp(string $mobile, string $code): bool
    {
        if (! $this->settings->isSmsLive()) {
            Log::info("OTP SMS [log] to {$mobile}: {$code}");

            return true;
        }

        $config = $this->settings->ippanelConfig();

        if (! $this->hasCredentials($config)) {
            Log::warning('IPPanel SMS not configured', ['mobile' => $mobile, 'code' => $code]);

            return false;
        }

        if (! empty($config['otp_pattern_code'])) {
            $result = $this->sendPattern(
                $mobile,
                $config['otp_pattern_code'],
                ['code' => $code],
                $config
            );

            return $result['success'];
        }

        $result = $this->sendWebservice($mobile, "کد تأیید پوشه: {$code}", $config);

        return $result['success'];
    }

    public function sendInvite(string $mobile, string $officeName, string $inviterName): bool
    {
        $template = $this->settings->get(
            'invite_sms_template',
            'شما به دفتر {office} در پوشه دعوت شدید. با شماره موبایل خود وارد شوید.'
        );

        $message = str_replace(
            ['{office}', '{inviter}'],
            [$officeName, $inviterName],
            $template
        );

        if (! $this->settings->isSmsLive()) {
            Log::info("Invite SMS [log] to {$mobile}: {$message}");

            return true;
        }

        $config = $this->settings->ippanelConfig();

        if (! empty($config['invite_pattern_code'])) {
            $result = $this->sendPattern(
                $mobile,
                $config['invite_pattern_code'],
                ['office' => $officeName],
                $config
            );

            return $result['success'];
        }

        $result = $this->sendWebservice($mobile, $message, $config);

        return $result['success'];
    }

    public function test(string $mobile, string $message = 'تست پیامک پوشه', ?array $configOverride = null): array
    {
        $config = $configOverride
            ? array_merge($this->settings->ippanelConfig(), $configOverride)
            : $this->settings->ippanelConfig();

        if (! $this->hasCredentials($config)) {
            return [
                'success' => false,
                'message' => 'تنظیمات IPPanel ناقص است. کلید API یا نام کاربری/رمز عبور را وارد و ذخیره کنید.',
            ];
        }

        if (empty($config['from_number'])) {
            return [
                'success' => false,
                'message' => 'شماره ارسال‌کننده (from_number) الزامی است. مثال: +983000505',
            ];
        }

        $result = $this->sendWebservice($mobile, $message, $config, forceLive: true);

        if ($result['success']) {
            return [
                'success' => true,
                'message' => 'پیامک تست با موفقیت ارسال شد.',
                'details' => $result['details'] ?? null,
            ];
        }

        return [
            'success' => false,
            'message' => $result['message'] ?? 'خطا در ارسال پیامک',
            'details' => $result['details'] ?? null,
        ];
    }

    private function sendWebservice(string $mobile, string $message, array $config, bool $forceLive = false): array
    {
        if (! $forceLive && ! $this->settings->isSmsLive()) {
            Log::info("SMS [log] to {$mobile}: {$message}");

            return ['success' => true, 'message' => 'logged'];
        }

        $auth = $this->resolveAuth($config);
        if (! $auth['token']) {
            return ['success' => false, 'message' => $auth['error'] ?? 'خطا در احراز هویت IPPanel'];
        }

        $baseUrl = $this->apiBase($config);

        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => $auth['token'],
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->post("{$baseUrl}/send", [
                    'sending_type' => 'webservice',
                    'from_number' => $this->normalizeSender($config['from_number']),
                    'message' => $message,
                    'params' => [
                        'recipients' => [$this->toE164($mobile)],
                    ],
                ]);

            return $this->parseResponse($response);
        } catch (\Throwable $e) {
            Log::error('IPPanel webservice exception', ['error' => $e->getMessage()]);

            return ['success' => false, 'message' => 'خطای اتصال: '.$e->getMessage()];
        }
    }

    private function sendPattern(string $mobile, string $patternCode, array $params, array $config): array
    {
        $auth = $this->resolveAuth($config);
        if (! $auth['token']) {
            return ['success' => false, 'message' => $auth['error'] ?? 'خطا در احراز هویت IPPanel'];
        }

        $baseUrl = $this->apiBase($config);

        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => $auth['token'],
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->post("{$baseUrl}/send", [
                    'sending_type' => 'pattern',
                    'from_number' => $this->normalizeSender($config['from_number']),
                    'code' => $patternCode,
                    'recipients' => [$this->toE164($mobile)],
                    'params' => $params,
                ]);

            return $this->parseResponse($response);
        } catch (\Throwable $e) {
            Log::error('IPPanel pattern exception', ['error' => $e->getMessage()]);

            return ['success' => false, 'message' => 'خطای اتصال: '.$e->getMessage()];
        }
    }

    private function resolveAuth(array $config): array
    {
        if (! empty($config['api_key'])) {
            return ['token' => trim($config['api_key'])];
        }

        if (empty($config['username']) || empty($config['password'])) {
            return ['token' => null, 'error' => 'کلید API یا نام کاربری/رمز عبور IPPanel وارد نشده'];
        }

        $baseUrl = rtrim($config['base_url'] ?? 'https://edge.ippanel.com/v1', '/');

        try {
            $response = Http::timeout(20)
                ->withHeaders(['Content-Type' => 'application/json', 'Accept' => 'application/json'])
                ->post("{$baseUrl}/api/acl/auth/login", [
                    'username' => $config['username'],
                    'password' => $config['password'],
                ]);

            $body = $response->json();
            $token = $body['data']['token'] ?? null;
            $method = $body['data']['method'] ?? 'login';

            if ($token && ($body['meta']['status'] ?? false)) {
                if ($method !== 'login') {
                    return [
                        'token' => null,
                        'error' => 'حساب IPPanel احراز هویت دو مرحله‌ای (۲FA) دارد. از کلید API در پنل IPPanel استفاده کنید.',
                    ];
                }

                return ['token' => $token];
            }

            $errorMsg = $body['meta']['message'] ?? $response->body();

            return ['token' => null, 'error' => 'ورود IPPanel ناموفق: '.$errorMsg];
        } catch (\Throwable $e) {
            return ['token' => null, 'error' => 'خطا در ورود IPPanel: '.$e->getMessage()];
        }
    }

    private function parseResponse(\Illuminate\Http\Client\Response $response): array
    {
        $body = $response->json() ?? [];
        $meta = $body['meta'] ?? [];
        $status = $meta['status'] ?? false;
        $message = $meta['message'] ?? ($body['message'] ?? $response->body());

        if ($response->successful() && $status) {
            return [
                'success' => true,
                'message' => $message,
                'details' => $body['data'] ?? null,
            ];
        }

        Log::error('IPPanel API error', [
            'http_status' => $response->status(),
            'body' => $body,
        ]);

        $errors = $meta['errors'] ?? [];
        $errorDetail = is_array($errors) ? json_encode($errors, JSON_UNESCAPED_UNICODE) : (string) $errors;

        return [
            'success' => false,
            'message' => $message.($errorDetail ? " ({$errorDetail})" : ''),
            'details' => $body,
        ];
    }

    private function hasCredentials(array $config): bool
    {
        return ! empty($config['api_key'])
            || (! empty($config['username']) && ! empty($config['password']));
    }

    private function apiBase(array $config): string
    {
        $base = rtrim($config['base_url'] ?? 'https://edge.ippanel.com/v1', '/');

        if (str_ends_with($base, '/api')) {
            return $base;
        }

        return $base.'/api';
    }

    private function normalizeSender(string $number): string
    {
        $number = trim($number);

        if (! str_starts_with($number, '+')) {
            if (str_starts_with($number, '0')) {
                $number = '+98'.substr($number, 1);
            } else {
                $number = '+'.$number;
            }
        }

        return $number;
    }

    private function toE164(string $mobile): string
    {
        return $this->normalizeSender($mobile);
    }
}
