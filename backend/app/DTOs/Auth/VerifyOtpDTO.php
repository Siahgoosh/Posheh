<?php

namespace App\DTOs\Auth;

readonly class VerifyOtpDTO
{
    public function __construct(
        public string $mobile,
        public string $code,
        public ?string $deviceId = null,
        public ?string $deviceName = null,
        public ?string $platform = null,
    ) {}
}
