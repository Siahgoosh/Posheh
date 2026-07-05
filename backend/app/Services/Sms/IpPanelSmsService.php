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
        $apiKey = $config['api_key'];
        $fromNumber = $config['from_number'];
        $patternCode = $config['otp_pattern_code'];
        $baseUrl = rtrim($config['base_url'] ?? 'https://edge.ippanel.com/v1', '/');

        if (! $apiKey || ! $fromNumber) {
            Log::warning('IPPanel SMS not configured', ['mobile' => $mobile, 'code' => $code]);

            return false;
        }

        return $this->dispatch($mobile, $code, $apiKey, $fromNumber, $patternCode, $baseUrl, 'code');
    }

    public function sendInvite(string $mobile, string $officeName, string $inviterName): bool
    {
        $template = $this->settings->get('invite_sms_template',
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
        $patternCode = $config['invite_pattern_code'];

        if ($patternCode) {
            return $this->dispatch(
                $mobile,
                $officeName,
                $config['api_key'],
                $config['from_number'],
                $patternCode,
                rtrim($config['base_url'] ?? 'https://edge.ippanel.com/v1', '/'),
                'office'
            );
        }

        return $this->send($mobile, $message);
    }

    public function send(string $mobile, string $message): bool
    {
        if (! $this->settings->isSmsLive()) {
            Log::info("SMS [log] to {$mobile}: {$message}");

            return true;
        }

        $config = $this->settings->ippanelConfig();

        if (! $config['api_key'] || ! $config['from_number']) {
            Log::info("SMS [log] to {$mobile}: {$message}");

            return false;
        }

        try {
            $baseUrl = rtrim($config['base_url'] ?? 'https://edge.ippanel.com/v1', '/');
            $response = Http::get("{$baseUrl}/api/send/webservice", [
                'from' => $config['from_number'],
                'to' => $this->toE164($mobile),
                'message' => $message,
                'apikey' => $config['api_key'],
            ]);

            return $response->successful();
        } catch (\Throwable $e) {
            Log::error('IPPanel SMS exception', ['error' => $e->getMessage()]);

            return false;
        }
    }

    public function test(string $mobile, string $message = 'تست پیامک پوشه'): array
    {
        $config = $this->settings->ippanelConfig();

        if (! $config['api_key'] || ! $config['from_number']) {
            return ['success' => false, 'message' => 'تنظیمات IPPanel ناقص است.'];
        }

        try {
            $baseUrl = rtrim($config['base_url'] ?? 'https://edge.ippanel.com/v1', '/');
            $response = Http::get("{$baseUrl}/api/send/webservice", [
                'from' => $config['from_number'],
                'to' => $this->toE164($mobile),
                'message' => $message,
                'apikey' => $config['api_key'],
            ]);

            if ($response->successful()) {
                return ['success' => true, 'message' => 'پیامک تست ارسال شد.'];
            }

            return ['success' => false, 'message' => 'خطا: '.$response->body()];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function dispatch(
        string $mobile,
        string $paramValue,
        string $apiKey,
        string $fromNumber,
        string $patternCode,
        string $baseUrl,
        string $paramKey,
    ): bool {
        $recipient = $this->toE164($mobile);

        try {
            $response = Http::withHeaders([
                'Authorization' => $apiKey,
                'Content-Type' => 'application/json',
            ])->post("{$baseUrl}/api/send", [
                'sending_type' => 'pattern',
                'from_number' => $fromNumber,
                'code' => $patternCode,
                'recipients' => [$recipient],
                'params' => [$paramKey => $paramValue],
            ]);

            if ($response->successful()) {
                Log::info('IPPanel pattern SMS sent', ['mobile' => $mobile]);

                return true;
            }

            Log::error('IPPanel SMS failed', ['status' => $response->status(), 'body' => $response->body()]);
        } catch (\Throwable $e) {
            Log::error('IPPanel SMS exception', ['error' => $e->getMessage()]);
        }

        return false;
    }

    private function toE164(string $mobile): string
    {
        $digits = preg_replace('/\D/', '', $mobile);

        if (str_starts_with($digits, '0')) {
            $digits = '98'.substr($digits, 2);
        }

        return '+'.$digits;
    }
}
