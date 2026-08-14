<?php

namespace App\Services\Sms;

use App\Contracts\SmsProviderInterface;
use Illuminate\Support\Facades\Log;

/**
 * Adapter: existing IpPanelSmsService → SmsProviderInterface for Content Planner.
 */
class IpPanelSmsProvider implements SmsProviderInterface
{
    public function __construct(private readonly IpPanelSmsService $sms) {}

    public function sendSms(string $mobile, string $message): array
    {
        try {
            $result = $this->sms->sendPlain($mobile, $message);
            $ok = (bool) ($result['success'] ?? false);

            return [
                'ok' => $ok,
                'provider' => 'ippanel',
                'message_id' => $result['message_id'] ?? $result['bulk_id'] ?? null,
                'error' => $ok ? null : ($result['message'] ?? 'ارسال پیامک ناموفق بود'),
                'raw' => $result,
            ];
        } catch (\Throwable $e) {
            Log::error('content_planner.sms_provider_exception', [
                'mobile' => $mobile,
                'error' => $e->getMessage(),
            ]);

            return [
                'ok' => false,
                'provider' => 'ippanel',
                'message_id' => null,
                'error' => $e->getMessage(),
                'raw' => null,
            ];
        }
    }
}
