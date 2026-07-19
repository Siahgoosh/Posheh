<?php

namespace App\Services\Payment;

use App\Services\Settings\SystemSettingsService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ZibalService
{
    private const API_BASE = 'https://gateway.zibal.ir/v1';

    public function __construct(
        private readonly SystemSettingsService $settings,
    ) {}

    public function request(int $amountToman, string $description, string $callbackUrl, int $orderId, ?string $mobile = null): array
    {
        $merchant = $this->merchant();

        if (! $merchant) {
            throw ValidationException::withMessages([
                'payment' => ['درگاه زیبال پیکربندی نشده. ZIBAL_MERCHANT را در .env تنظیم کنید.'],
            ]);
        }

        $payload = [
            'merchant' => $merchant,
            'amount' => $amountToman * 10,
            'callbackUrl' => $callbackUrl,
            'orderId' => (string) $orderId,
            'description' => $description,
        ];

        if ($mobile) {
            $payload['mobile'] = $mobile;
        }

        $response = Http::timeout(30)->post(self::API_BASE.'/request', $payload);
        $body = $response->json();

        if (($body['result'] ?? 0) !== 100 || empty($body['trackId'])) {
            Log::error('Zibal request failed', ['body' => $body]);
            throw ValidationException::withMessages([
                'payment' => [$body['message'] ?? 'خطا در اتصال به زیبال'],
            ]);
        }

        $trackId = (string) $body['trackId'];

        return [
            'track_id' => $trackId,
            'redirect_url' => 'https://gateway.zibal.ir/start/'.$trackId,
        ];
    }

    public function verify(string $trackId, int $expectedAmountToman): array
    {
        $merchant = $this->merchant();

        if (! $merchant) {
            return [
                'success' => false,
                'message' => 'درگاه زیبال پیکربندی نشده.',
            ];
        }

        $response = Http::timeout(30)->post(self::API_BASE.'/verify', [
            'merchant' => $merchant,
            'trackId' => $trackId,
        ]);

        $body = $response->json();
        $result = (int) ($body['result'] ?? 0);

        if (! in_array($result, [100, 201], true)) {
            Log::warning('Zibal verify failed', ['body' => $body, 'trackId' => $trackId]);

            return [
                'success' => false,
                'code' => $result,
                'message' => $body['message'] ?? 'تأیید پرداخت ناموفق',
            ];
        }

        $paidRials = (int) ($body['amount'] ?? 0);
        if ($paidRials > 0 && $paidRials !== $expectedAmountToman * 10) {
            Log::warning('Zibal amount mismatch', [
                'expected_rials' => $expectedAmountToman * 10,
                'paid_rials' => $paidRials,
                'trackId' => $trackId,
            ]);

            return [
                'success' => false,
                'message' => 'مبلغ پرداخت با سفارش مطابقت ندارد.',
            ];
        }

        return [
            'success' => true,
            'code' => $result,
            'ref_id' => (string) ($body['refNumber'] ?? $trackId),
            'card_pan' => $body['cardNumber'] ?? null,
        ];
    }

    private function merchant(): ?string
    {
        $config = $this->settings->zibalConfig();
        $merchant = $config['merchant'] ?? null;
        $sandbox = (bool) ($config['sandbox'] ?? false);

        if ($sandbox && ! $merchant) {
            return 'zibal';
        }

        return $merchant ?: null;
    }
}
