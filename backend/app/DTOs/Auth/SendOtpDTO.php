<?php

namespace App\DTOs\Auth;

readonly class SendOtpDTO
{
    public function __construct(
        public string $mobile,
        public string $purpose = 'login',
    ) {}
}
