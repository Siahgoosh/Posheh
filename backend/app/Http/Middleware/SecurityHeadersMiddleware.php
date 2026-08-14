<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Safe baseline security headers. CSP is opt-in via config to avoid breaking production.
 */
class SecurityHeadersMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        if (! config('performance.security_headers.enabled', true)) {
            return $response;
        }

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        if (config('performance.security_headers.hsts', true) && $request->isSecure()) {
            $max = (int) config('performance.security_headers.hsts_max_age', 31536000);
            $response->headers->set('Strict-Transport-Security', "max-age={$max}; includeSubDomains");
        }

        if (config('performance.security_headers.csp_enabled', false)) {
            $response->headers->set(
                'Content-Security-Policy',
                (string) config('performance.security_headers.csp')
            );
        }

        return $response;
    }
}
