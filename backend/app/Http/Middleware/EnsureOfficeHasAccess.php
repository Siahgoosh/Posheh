<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOfficeHasAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->isSuperAdmin()) {
            return $next($request);
        }

        $office = $user->office;

        if (! $office) {
            return response()->json([
                'message' => 'ابتدا دفتر خود را ثبت کنید.',
                'code' => 'NO_OFFICE',
            ], 403);
        }

        if ($office->hasAccess()) {
            return $next($request);
        }

        return response()->json([
            'message' => 'برای استفاده از نرم‌افزار ابتدا پلن اشتراک را خریداری کنید.',
            'code' => 'SUBSCRIPTION_REQUIRED',
        ], 402);
    }
}
