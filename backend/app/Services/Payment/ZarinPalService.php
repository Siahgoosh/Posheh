<?php

namespace App\Services\Payment;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ZarinPalService
{
    public function request(int $amount, string $description, string $callbackUrl, ?string $mobile = null): array
    {
        $merchantId = config('services.zarinpal.merchant_id');
        $sandbox = (bool) config('services.zarinpal.sandbox', true);

        if (! $merchantId) {
            throw ValidationException::withMessages([
                'payment' => ['درگاه زرین‌پال پیکربندی نشده. ZARINPAL_MERCHANT_ID را در .env تنظیم کنید.'],
            ]);
        }

        $baseUrl = $sandbox
            ? 'https://sandbox.zarinpal.com/pg/v4/payment'
            : 'https://api.zarinpal.com/pg/v4/payment';

        $payload = [
            'merchant_id' => $merchantId,
            'amount' => $amount,
            'description' => $description,
            'callback_url' => $callbackUrl,
        ];

        if ($mobile) {
            $payload['metadata'] = ['mobile' => $mobile];
        }

        $response = Http::timeout(30)->post("{$baseUrl}/request.json", $payload);
        $body = $response->json();

        if (($body['data']['code'] ?? 0) !== 100) {
            Log::error('ZarinPal request failed', ['body' => $body]);
            throw ValidationException::withMessages([
                'payment' => [$body['errors']['message'] ?? 'خطا در اتصال به زرین‌پال'],
            ]);
        }

        $authority = $body['data']['authority'];
        $gateway = $sandbox
            ? "https://sandbox.zarinpal.com/pg/StartPay/{$authority}"
            : "https://www.zarinpal.com/pg/StartPay/{$authority}";

        return [
            'authority' => $authority,
            'redirect_url' => $gateway,
        ];
    }

    public function verify(string $authority, int $amount): array
    {
        $merchantId = config('services.zarinpal.merchant_id');
        $sandbox = (bool) config('services.zarinpal.sandbox', true);

        $baseUrl = $sandbox
            ? 'https://sandbox.zarinpal.com/pg/v4/payment'
            : 'https://api.zarinpal.com/pg/v4/payment';

        $response = Http::timeout(30)->post("{$baseUrl}/verify.json", [
            'merchant_id' => $merchantId,
            'amount' => $amount,
            'authority' => $authority,
        ]);

        $body = $response->json();
        $code = $body['data']['code'] ?? 0;

        if (! in_array($code, [100, 101], true)) {
            Log::warning('ZarinPal verify failed', ['body' => $body, 'authority' => $authority]);

            return [
                'success' => false,
                'code' => $code,
                'message' => $body['errors']['message'] ?? 'تأیید پرداخت ناموفق',
            ];
        }

        return [
            'success' => true,
            'code' => $code,
            'ref_id' => (string) ($body['data']['ref_id'] ?? ''),
            'card_pan' => $body['data']['card_pan'] ?? null,
        ];
    }
}
