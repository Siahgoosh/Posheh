<?php

namespace App\Jobs;

use App\Services\Sms\IpPanelSmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SendInviteSmsJob
{
    use Dispatchable, Queueable, SerializesModels;

    public function __construct(
        public string $mobile,
        public string $officeName,
        public string $inviterName,
    ) {}

    public function handle(IpPanelSmsService $sms): void
    {
        $sms->sendInvite($this->mobile, $this->officeName, $this->inviterName);
    }
}
