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
            'gateway' => ['required', 'string', 'in:zarinpal,cafe_bazaar,wallet'],
        ]);

        $result = $this->subscriptionService->subscribe(
            $request->user()->office,
            $request->input('plan_id'),
            PaymentGateway::from($request->input('gateway'))
        );

        return response()->json($result);
    }

    public function zarinpalCallback(Request $request): JsonResponse
    {
        $result = $this->subscriptionService->verifyZarinPal(
            $request->input('Authority'),
            $request->input('Status')
        );

        return response()->json($result);
    }
}
