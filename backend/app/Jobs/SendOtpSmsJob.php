<?php

namespace App\Jobs;

use App\Services\Sms\IpPanelSmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendOtpSmsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 30;

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

            $this->fail(new \RuntimeException($result['message'] ?? 'OTP SMS failed'));
        } catch (\Throwable $e) {
            Log::error('OTP SMS job exception', [
                'mobile' => $this->maskMobile($this->mobile),
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    private function maskMobile(string $mobile): string
    {
        return substr($mobile, 0, 4).'***'.substr($mobile, -2);
    }
}
