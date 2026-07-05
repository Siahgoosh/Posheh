<?php

namespace App\Services\Payment;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AqayepardakhtService
{
    private string $baseUrl = 'https://panel.aqayepardakht.ir';

    public function create(int $amount, string $callback, array $extra = []): array
    {
        $pin = config('services.aqayepardakht.pin');
        $sandbox = config('services.aqayepardakht.sandbox', true);

        if (! $pin) {
            throw new \RuntimeException('درگاه آقای پرداخت تنظیم نشده است.');
        }

        $payload = array_merge([
            'pin' => $sandbox ? 'sandbox' : $pin,
            'amount' => $amount,
            'callback' => $callback,
            'callback_method' => 'GET',
        ], $extra);

        $response = Http::asJson()->post("{$this->baseUrl}/api/v2/create", $payload);
        $data = $response->json();

        if (! $response->successful() || ($data['status'] ?? '') !== 'success') {
            Log::error('Aqayepardakht create failed', ['response' => $data]);
            throw new \RuntimeException($data['code'] ?? 'خطا در ایجاد تراکنش پرداخت');
        }

        $transId = $data['transid'];
        $payPath = $sandbox
            ? "/startpay/sandbox/{$transId}"
            : "/startpay/{$transId}";

        return [
            'transid' => $transId,
            'redirect_url' => $this->baseUrl.$payPath,
        ];
    }

    public function verify(int $amount, string $transId): bool
    {
        $pin = config('services.aqayepardakht.pin');
        $sandbox = config('services.aqayepardakht.sandbox', true);

        $response = Http::asJson()->post("{$this->baseUrl}/api/v2/verify", [
            'pin' => $sandbox ? 'sandbox' : $pin,
            'amount' => $amount,
            'transid' => $transId,
        ]);

        $data = $response->json();

        return $response->successful()
            && ($data['status'] ?? '') === 'success'
            && ($data['code'] ?? '') === '1';
    }
}
