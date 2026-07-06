<?php

namespace App\Services\Payment;

use App\Services\Settings\SystemSettingsService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AqayepardakhtService
{
    private string $baseUrl = 'https://panel.aqayepardakht.ir';

    public function __construct(
        private readonly SystemSettingsService $settings,
    ) {}

    public function create(int $amount, string $callback, array $extra = []): array
    {
        $config = $this->settings->aqayepardakhtConfig();
        $pin = $config['pin'];
        $sandbox = $config['sandbox'];

        if (! $pin && ! $sandbox) {
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
        $config = $this->settings->aqayepardakhtConfig();

        $response = Http::asJson()->post("{$this->baseUrl}/api/v2/verify", [
            'pin' => $config['sandbox'] ? 'sandbox' : $config['pin'],
            'amount' => $amount,
            'transid' => $transId,
        ]);

        $data = $response->json();

        return $response->successful()
            && ($data['status'] ?? '') === 'success'
            && ($data['code'] ?? '') === '1';
    }
}
