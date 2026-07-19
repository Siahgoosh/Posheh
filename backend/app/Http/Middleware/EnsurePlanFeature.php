<?php

namespace App\Http\Middleware;

use App\Services\Subscription\SubscriptionAccessService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePlanFeature
{
    public function __construct(
        private readonly SubscriptionAccessService $accessService,
    ) {}

    public function handle(Request $request, Closure $next, string $feature): Response
    {
        $user = $request->user();

        if ($user?->isSuperAdmin()) {
            return $next($request);
        }

        if (! $this->accessService->officeHasFeature($user?->office, $feature)) {
            return response()->json([
                'message' => 'این قابلیت در پلن اشتراک شما فعال نیست.',
                'feature' => $feature,
                'upgrade_required' => true,
            ], 403);
        }

        return $next($request);
    }
}
