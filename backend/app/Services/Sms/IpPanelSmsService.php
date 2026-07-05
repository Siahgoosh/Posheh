<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class IpPanelSmsService
{
    public function sendOtp(string $mobile, string $code): bool
    {
        $provider = config('services.sms.provider', 'log');

        if ($provider === 'log' || ! app()->environment('production')) {
            Log::info("OTP SMS [log] to {$mobile}: {$code}");

            return true;
        }

        $apiKey = config('services.ippanel.api_key');
        $fromNumber = config('services.ippanel.from_number');
        $patternCode = config('services.ippanel.otp_pattern_code');
        $baseUrl = rtrim(config('services.ippanel.base_url', 'https://edge.ippanel.com/v1'), '/');

        if (! $apiKey || ! $fromNumber) {
            Log::warning('IPPanel SMS not configured, OTP logged only', ['mobile' => $mobile, 'code' => $code]);

            return false;
        }

        $recipient = $this->toE164($mobile);

        try {
            if ($patternCode) {
                $response = Http::withHeaders([
                    'Authorization' => $apiKey,
                    'Content-Type' => 'application/json',
                ])->post("{$baseUrl}/api/send", [
                    'sending_type' => 'pattern',
                    'from_number' => $fromNumber,
                    'code' => $patternCode,
                    'recipients' => [$recipient],
                    'params' => ['code' => $code],
                ]);
            } else {
                $message = "کد تأیید پوشه: {$code}";
                $response = Http::get("{$baseUrl}/api/send/webservice", [
                    'from' => $fromNumber,
                    'to' => $recipient,
                    'message' => $message,
                    'apikey' => $apiKey,
                ]);
            }

            if ($response->successful()) {
                Log::info('OTP SMS sent via IPPanel', ['mobile' => $mobile]);

                return true;
            }

            Log::error('IPPanel SMS failed', [
                'mobile' => $mobile,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        } catch (\Throwable $e) {
            Log::error('IPPanel SMS exception', ['mobile' => $mobile, 'error' => $e->getMessage()]);
        }

        return false;
    }

    public function send(string $mobile, string $message): bool
    {
        $apiKey = config('services.ippanel.api_key');
        $fromNumber = config('services.ippanel.from_number');
        $baseUrl = rtrim(config('services.ippanel.base_url', 'https://edge.ippanel.com/v1'), '/');

        if (! $apiKey || ! $fromNumber) {
            Log::info("SMS [log] to {$mobile}: {$message}");

            return false;
        }

        try {
            $response = Http::get("{$baseUrl}/api/send/webservice", [
                'from' => $fromNumber,
                'to' => $this->toE164($mobile),
                'message' => $message,
                'apikey' => $apiKey,
            ]);

            return $response->successful();
        } catch (\Throwable $e) {
            Log::error('IPPanel SMS exception', ['error' => $e->getMessage()]);

            return false;
        }
    }

    private function toE164(string $mobile): string
    {
        $digits = preg_replace('/\D/', '', $mobile);

        if (str_starts_with($digits, '0')) {
            $digits = '98'.substr($digits, 1);
        }

        if (! str_starts_with($digits, '+')) {
            $digits = '+'.$digits;
        }

        return $digits;
    }
}
