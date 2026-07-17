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
            'discount_code' => ['nullable', 'string', 'max:50'],
        ]);

        $result = $this->subscriptionService->subscribe(
            $request->user()->office,
            $request->user(),
            $request->input('plan_id'),
            PaymentGateway::from($request->input('gateway')),
            $request->input('discount_code'),
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

        try {
            $result = $this->subscriptionService->verifyZibal(
                (string) $request->input('trackId'),
                (string) $request->input('success') === '1'
            );

            return redirect($frontend.'/payment/callback?status=success&ref='.($result['payment']->ref_id ?? ''));
        } catch (\Throwable $e) {
            return redirect($frontend.'/payment/callback?status=failed&message='.urlencode($e->getMessage()));
        }
    }
}
