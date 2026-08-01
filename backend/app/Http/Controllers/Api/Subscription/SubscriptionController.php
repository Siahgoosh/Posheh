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
            'gateway' => ['required', 'string', 'in:zibal,cafe_bazaar,wallet'],
        ]);

        $result = $this->subscriptionService->subscribe(
            $request->user()->office,
            $request->input('plan_id'),
            PaymentGateway::from($request->input('gateway'))
        );

        return response()->json($result);
    }

    public function verifyBazaarPurchase(Request $request): JsonResponse
    {
        $data = $request->validate([
            'plan_id' => ['required', 'integer', 'exists:subscription_plans,id'],
            'product_id' => ['required', 'string', 'max:100'],
            'purchase_token' => ['required', 'string', 'max:500'],
            'order_id' => ['nullable', 'string', 'max:255'],
        ]);

        $result = $this->subscriptionService->verifyCafeBazaarPurchase(
            $request->user()->office,
            $data['plan_id'],
            $data['product_id'],
            $data['purchase_token'],
            $data['order_id'] ?? null,
        );

        return response()->json($result);
    }

    public function zibalCallback(Request $request)
    {
        $frontend = rtrim(config('app.frontend_url', config('app.url')), '/');

        try {
            $result = $this->subscriptionService->verifyZibal(
                (string) $request->input('trackId'),
                (string) $request->input('success') === '1'
            );

            $type = $result['type'] ?? 'subscription';
            $query = 'status=success&ref='.urlencode((string) ($result['payment']->ref_id ?? '')).'&type='.$type;

            return redirect($frontend.'/payment/callback?'.$query);
        } catch (\Throwable $e) {
            return redirect($frontend.'/payment/callback?status=failed&message='.urlencode($e->getMessage()));
        }
    }
}
