<?php

namespace App\Services\Payment;

use App\Services\Settings\SystemSettingsService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class CafeBazaarService
{
    public function __construct(
        private readonly SystemSettingsService $settings,
    ) {}

    public function verifyPurchase(string $productId, string $purchaseToken): array
    {
        $package = $this->settings->get('cafe_bazaar_package', config('services.cafe_bazaar.package', 'ir.posheh.app'));
        $token = $this->settings->get('cafe_bazaar_access_token', config('services.cafe_bazaar.access_token'));

        if (! $token) {
            throw ValidationException::withMessages([
                'payment' => ['توکن کافه‌بازار در تنظیمات سیستم پیکربندی نشده است.'],
            ]);
        }

        $url = "https://pardakht.cafebazaar.ir/devapi/v2/api/validate/{$package}/inapp/{$productId}/purchases/{$purchaseToken}/";

        $response = Http::withToken($token)->timeout(30)->get($url);
        $body = $response->json();

        if (! $response->successful() || empty($body['purchaseState']) || (int) $body['purchaseState'] !== 0) {
            Log::warning('Cafe Bazaar verify failed', ['body' => $body, 'status' => $response->status()]);

            return [
                'success' => false,
                'message' => 'خرید کافه‌بازار تأیید نشد.',
            ];
        }

        return [
            'success' => true,
            'product_id' => $productId,
            'purchase_token' => $purchaseToken,
            'consumption_state' => $body['consumptionState'] ?? null,
        ];
    }
}
