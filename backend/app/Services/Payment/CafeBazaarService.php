<?php

namespace App\Services\Payment;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class CafeBazaarService
{
    private const API_BASE = 'https://pardakht.cafebazaar.ir/devapi/v2/api/validate';

    /**
     * Verify a Cafe Bazaar subscription purchase token.
     *
     * @return array{valid: bool, purchase_state: int, consumption_state?: int}
     */
    public function verifySubscriptionPurchase(
        string $packageName,
        string $subscriptionId,
        string $purchaseToken,
    ): array {
        $apiToken = config('services.cafe_bazaar.api_token');

        if (! $apiToken) {
            if (app()->environment(['local', 'testing'])) {
                Log::warning('Cafe Bazaar API token missing — accepting purchase in dev mode');

                return ['valid' => true, 'purchase_state' => 0];
            }

            throw ValidationException::withMessages([
                'payment' => ['درگاه پرداخت کافه‌بازار پیکربندی نشده است.'],
            ]);
        }

        $url = sprintf(
            '%s/%s/subscriptions/%s/purchases/%s/',
            self::API_BASE,
            $packageName,
            $subscriptionId,
            $purchaseToken,
        );

        $response = Http::withToken($apiToken)
            ->acceptJson()
            ->timeout(30)
            ->get($url);

        if (! $response->successful()) {
            Log::error('Cafe Bazaar verify failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw ValidationException::withMessages([
                'payment' => ['تأیید خرید از کافه‌بازار ناموفق بود.'],
            ]);
        }

        $body = $response->json();
        $purchaseState = (int) ($body['purchaseState'] ?? -1);

        // 0 = purchased, 1 = canceled, 2 = pending
        if ($purchaseState !== 0) {
            throw ValidationException::withMessages([
                'payment' => ['وضعیت خرید در کافه‌بازار معتبر نیست.'],
            ]);
        }

        return [
            'valid' => true,
            'purchase_state' => $purchaseState,
            'consumption_state' => (int) ($body['consumptionState'] ?? 0),
            'raw' => $body,
        ];
    }
}
