<?php

namespace App\Jobs;

use App\Services\Sms\IpPanelSmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SendOtpSmsJob
{
    use Dispatchable, Queueable, SerializesModels;

    public function __construct(
        public string $mobile,
        public string $code,
    ) {}

    public function handle(IpPanelSmsService $sms): array
    {
        return $sms->sendOtp($this->mobile, $this->code);
    }
}
