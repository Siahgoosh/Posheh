<?php

namespace App\Services\Payment;

use App\Services\Settings\SystemSettingsService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AqayepardakhtService
{
    private const API_BASE = 'https://panel.aqayepardakht.ir/api';

    public function __construct(
        private readonly SystemSettingsService $settings,
    ) {}

    public function request(int $amountToman, string $description, string $callbackUrl, int $orderId, ?string $mobile = null): array
    {
        $pin = $this->settings->aqayepardakhtConfig()['pin'];

        if (! $pin) {
            throw ValidationException::withMessages([
                'payment' => ['پین آقای پرداخت در تنظیمات سیستم پیکربندی نشده است.'],
            ]);
        }

        $payload = [
            'pin' => $pin,
            'amount' => $amountToman * 10,
            'callback' => $callbackUrl,
            'invoice_id' => (string) $orderId,
            'description' => $description,
        ];

        if ($mobile) {
            $payload['mobile'] = $mobile;
        }

        $response = Http::timeout(30)->post(self::API_BASE.'/create', $payload);
        $body = $response->json();

        if (($body['status'] ?? '') !== 'success' || empty($body['transid'])) {
            Log::error('Aqayepardakht request failed', ['body' => $body]);
            throw ValidationException::withMessages([
                'payment' => [$body['message'] ?? 'خطا در اتصال به آقای پرداخت'],
            ]);
        }

        $transId = (string) $body['transid'];

        return [
            'track_id' => $transId,
            'redirect_url' => 'https://panel.aqayepardakht.ir/startpay/'.$transId,
        ];
    }

    public function verify(string $transId, int $expectedAmountToman): array
    {
        $pin = $this->settings->aqayepardakhtConfig()['pin'];

        if (! $pin) {
            return ['success' => false, 'message' => 'پین آقای پرداخت پیکربندی نشده.'];
        }

        $response = Http::timeout(30)->post(self::API_BASE.'/verify', [
            'pin' => $pin,
            'transid' => $transId,
        ]);

        $body = $response->json();

        if (($body['status'] ?? '') !== 'success') {
            return ['success' => false, 'message' => $body['message'] ?? 'تأیید پرداخت ناموفق بود.'];
        }

        $paidAmount = (int) (($body['amount'] ?? 0) / 10);

        if ($paidAmount > 0 && abs($paidAmount - $expectedAmountToman) > 100) {
            return ['success' => false, 'message' => 'مبلغ پرداخت با سفارش مطابقت ندارد.'];
        }

        return [
            'success' => true,
            'ref_id' => $body['cardnumber'] ?? $transId,
        ];
    }
}
