<?php

namespace App\Services\Sms;

use App\Jobs\SendOtpSmsJob;
use Illuminate\Support\Facades\Log;

class OtpSmsDispatcher
{
    public function dispatch(string $mobile, string $code): void
    {
        $this->trace('dispatch_requested', $mobile, ['code_length' => strlen($code)]);

        if ($this->spawnBackgroundSend($mobile, $code)) {
            $this->trace('background_spawned', $mobile);

            return;
        }

        $this->trace('sync_fallback', $mobile);
        SendOtpSmsJob::dispatchSync($mobile, $code);
        $this->trace('sync_completed', $mobile);
    }

    private function spawnBackgroundSend(string $mobile, string $code): bool
    {
        if (! $this->canExec()) {
            $this->trace('exec_disabled', $mobile);

            return false;
        }

        $logFile = storage_path('logs/otp-sms.log');
        $command = sprintf(
            'cd %s && nohup php artisan otp:send-sms %s %s >> %s 2>&1 &',
            escapeshellarg(base_path()),
            escapeshellarg($mobile),
            escapeshellarg($code),
            escapeshellarg($logFile),
        );

        @exec($command, $output, $exitCode);
        $this->trace('exec_called', $mobile, ['exit_code' => $exitCode]);

        return true;
    }

    private function canExec(): bool
    {
        if (! function_exists('exec')) {
            return false;
        }

        $disabled = array_filter(array_map('trim', explode(',', (string) ini_get('disable_functions'))));

        return ! in_array('exec', $disabled, true);
    }

    /** @param array<string, mixed> $context */
    private function trace(string $event, string $mobile, array $context = []): void
    {
        $masked = substr($mobile, 0, 4).'***'.substr($mobile, -2);
        $payload = array_merge(['event' => $event, 'mobile' => $masked], $context);
        $line = sprintf("[%s] %s\n", now()->toIso8601String(), json_encode($payload, JSON_UNESCAPED_UNICODE));

        @file_put_contents(storage_path('logs/otp-sms.log'), $line, FILE_APPEND | LOCK_EX);
        Log::warning('OTP SMS trace', $payload);
    }
}
