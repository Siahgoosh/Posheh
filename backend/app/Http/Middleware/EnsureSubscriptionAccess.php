<?php

namespace App\Http\Middleware;

use App\Services\Subscription\SubscriptionAccessService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSubscriptionAccess
{
    /** @var list<string> */
    private array $allowedPrefixes = [
        'api/v1/auth',
        'api/v1/plans',
        'api/v1/subscription',
        'api/v1/subscribe',
        'api/v1/payments',
        'api/v1/notifications',
    ];

    public function __construct(
        private readonly SubscriptionAccessService $accessService,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->isSuperAdmin()) {
            return $next($request);
        }

        if ($this->isAllowedRoute($request)) {
            return $next($request);
        }

        if (! $this->accessService->userHasAccess($user)) {
            return response()->json([
                'message' => 'دوره آزمایشی یا اشتراک شما به پایان رسیده است. لطفاً حساب خود را تمدید کنید.',
                'subscription_expired' => true,
                'access' => $this->accessService->accessStatus($user->office),
            ], 402);
        }

        return $next($request);
    }

    private function isAllowedRoute(Request $request): bool
    {
        $path = trim($request->path(), '/');

        foreach ($this->allowedPrefixes as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return true;
            }
        }

        return false;
    }
}
