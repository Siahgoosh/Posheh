<?php

namespace App\Jobs;

use App\Services\Sms\IpPanelSmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Runs via dispatchAfterResponse() — after the HTTP response is sent.
 * Intentionally not queued: OTP SMS must not depend on a separate queue worker.
 */
class SendOtpSmsJob
{
    use Dispatchable, Queueable, SerializesModels;

    public function __construct(
        public string $mobile,
        public string $code,
    ) {}

    public function handle(IpPanelSmsService $sms): void
    {
        try {
            $result = $sms->sendOtp($this->mobile, $this->code);

            if (($result['success'] ?? false)) {
                Log::info('OTP SMS sent', [
                    'mobile' => $this->maskMobile($this->mobile),
                    'method' => $result['method'] ?? null,
                ]);

                return;
            }

            Log::error('OTP SMS job failed', [
                'mobile' => $this->maskMobile($this->mobile),
                'message' => $result['message'] ?? null,
                'method' => $result['method'] ?? null,
            ]);
        } catch (\Throwable $e) {
            Log::error('OTP SMS job exception', [
                'mobile' => $this->maskMobile($this->mobile),
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function maskMobile(string $mobile): string
    {
        return substr($mobile, 0, 4).'***'.substr($mobile, -2);
    }
}
