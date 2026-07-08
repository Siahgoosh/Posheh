<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendOtpSmsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $mobile,
        public string $code,
    ) {}

    public function handle(): void
    {
        // Integrate with Kavenegar, Ghasedak, or Melipayamak SMS gateway
        Log::info('OTP SMS dispatched', ['mobile' => substr($this->mobile, 0, 4).'***'.substr($this->mobile, -2)]);
    }
}
