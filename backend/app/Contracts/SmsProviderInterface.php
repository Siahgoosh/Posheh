<?php

namespace App\Contracts;

interface SmsProviderInterface
{
    /**
     * @return array{ok: bool, provider?: string, message_id?: string|null, error?: string|null, raw?: mixed}
     */
    public function sendSms(string $mobile, string $message): array;
}
