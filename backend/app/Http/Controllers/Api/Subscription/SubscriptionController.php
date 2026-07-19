<?php

namespace App\Http\Controllers\Api\Subscription;

use App\Enums\PaymentGateway;
use App\Http\Controllers\Controller;
use App\Services\Subscription\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function __construct(
        private readonly SubscriptionService $subscriptionService,
    ) {}

    public function plans(): JsonResponse
    {
        return response()->json([
            'data' => $this->subscriptionService->getPlans(),
        ]);
    }

    public function current(Request $request): JsonResponse
    {
        $office = $request->user()->office;
        if (! $office) {
            return response()->json(['data' => null]);
        }

        $subscription = $this->subscriptionService->getCurrentSubscription($office);

        return response()->json([
            'data' => $subscription ? [
                'id' => $subscription->id,
                'status' => $subscription->status,
                'starts_at' => $subscription->starts_at?->toIso8601String(),
                'ends_at' => $subscription->ends_at?->toIso8601String(),
                'plan' => $subscription->plan,
            ] : null,
        ]);
    }

    public function subscribe(Request $request): JsonResponse
    {
        $request->validate([
            'plan_id' => ['required', 'integer', 'exists:subscription_plans,id'],
            'gateway' => ['required', 'string', 'in:zibal,cafe_bazaar,wallet,aqayepardakht'],
            'discount_code' => ['nullable', 'string', 'max:50'],
            'cafe_bazaar_product_id' => ['nullable', 'string', 'max:120'],
            'cafe_bazaar_purchase_token' => ['nullable', 'string', 'max:255'],
        ]);

        $result = $this->subscriptionService->subscribe(
            $request->user()->office,
            $request->user(),
            $request->input('plan_id'),
            PaymentGateway::from($request->input('gateway')),
            $request->input('discount_code'),
            $request->only(['cafe_bazaar_product_id', 'cafe_bazaar_purchase_token']),
        );

        return response()->json($result);
    }

    public function previewDiscount(Request $request): JsonResponse
    {
        $request->validate([
            'plan_id' => ['required', 'integer', 'exists:subscription_plans,id'],
            'discount_code' => ['required', 'string', 'max:50'],
        ]);

        return response()->json([
            'data' => $this->subscriptionService->previewDiscount(
                $request->input('discount_code'),
                (int) $request->input('plan_id'),
            ),
        ]);
    }

    public function zibalCallback(Request $request)
    {
        $frontend = rtrim(config('app.frontend_url', config('app.url')), '/');
        $purpose = 'subscription';

        try {
            $payment = \App\Models\Payment::where('authority', (string) $request->input('trackId'))->first();
            $purpose = $payment?->metadata['purpose'] ?? 'subscription';

            $result = $this->subscriptionService->verifyZibal(
                (string) $request->input('trackId'),
                (string) $request->input('success') === '1'
            );

            if ($purpose === 'wallet_topup') {
                return redirect($frontend.'/subscription?wallet=success&balance='.($result['balance'] ?? ''));
            }

            return redirect($frontend.'/payment/callback?status=success&ref='.($result['payment']->ref_id ?? ''));
        } catch (\Throwable $e) {
            if ($purpose === 'wallet_topup') {
                return redirect($frontend.'/subscription?wallet=failed&message='.urlencode($e->getMessage()));
            }

            return redirect($frontend.'/payment/callback?status=failed&message='.urlencode($e->getMessage()));
        }
    }

    public function aqayepardakhtCallback(Request $request)
    {
        $frontend = rtrim(config('app.frontend_url', config('app.url')), '/');

        try {
            $result = $this->subscriptionService->verifyAqayepardakht(
                (string) $request->input('transid'),
                (string) $request->input('status') === '1'
            );

            return redirect($frontend.'/payment/callback?status=success&ref='.($result['payment']->ref_id ?? ''));
        } catch (\Throwable $e) {
            return redirect($frontend.'/payment/callback?status=failed&message='.urlencode($e->getMessage()));
        }
    }
}
