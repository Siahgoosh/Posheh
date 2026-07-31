<?php

namespace App\Services\Sms;

use App\Jobs\SendOtpSmsJob;
use Illuminate\Support\Facades\Log;

class OtpSmsDispatcher
{
    /**
     * Queue OTP SMS without blocking the HTTP response.
     * Never call sendOtp synchronously here — JSPD/classic APIs timeout from abroad.
     */
    public function dispatch(string $mobile, string $code): void
    {
        $this->trace('dispatch_requested', $mobile, ['code_length' => strlen($code)]);

        if ($this->spawnBackgroundSend($mobile, $code)) {
            return;
        }

        try {
            SendOtpSmsJob::dispatch($mobile, $code)->afterResponse();
            $this->trace('queued_after_response', $mobile);
        } catch (\Throwable $e) {
            $this->trace('queue_failed', $mobile, ['error' => $e->getMessage()]);
            Log::error('OTP SMS could not be queued — user still gets OTP step', [
                'mobile' => $this->mask($mobile),
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function spawnBackgroundSend(string $mobile, string $code): bool
    {
        if (! $this->canExec()) {
            $this->trace('exec_disabled', $mobile);

            return false;
        }

        $logFile = storage_path('logs/otp-sms.log');
        $php = escapeshellarg(PHP_BINARY);
        $artisan = escapeshellarg(base_path('artisan'));
        $command = sprintf(
            'cd %s && nohup %s %s otp:send-sms %s %s >> %s 2>&1 &',
            escapeshellarg(base_path()),
            $php,
            $artisan,
            escapeshellarg($mobile),
            escapeshellarg($code),
            escapeshellarg($logFile),
        );

        @exec($command);
        $this->trace('background_spawned', $mobile);

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
        $payload = array_merge(['event' => $event, 'mobile' => $this->mask($mobile)], $context);
        $line = sprintf("[%s] %s\n", now()->toIso8601String(), json_encode($payload, JSON_UNESCAPED_UNICODE));

        @file_put_contents(storage_path('logs/otp-sms.log'), $line, FILE_APPEND | LOCK_EX);
        Log::info('OTP SMS trace', $payload);
    }

    private function mask(string $mobile): string
    {
        return substr($mobile, 0, 4).'***'.substr($mobile, -2);
    }
}
