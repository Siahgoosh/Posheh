<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOfficeIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->isSuperAdmin()) {
            $office = $user->office;

            if (! $office || ! $office->is_active) {
                return response()->json([
                    'message' => 'دفتر شما غیرفعال است. لطفاً با پشتیبانی تماس بگیرید.',
                ], 403);
            }
        }

        return $next($request);
    }
}
