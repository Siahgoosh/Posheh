<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendOtpSmsJob
{
    use Dispatchable, Queueable, SerializesModels;

    public function __construct(
        public string $mobile,
        public string $code,
    ) {}

    public function handle(): void
    {
        // Integrate with Kavenegar, Ghasedak, or Melipayamak SMS gateway
        Log::info("OTP sent to {$this->mobile}: {$this->code}");
    }
}
