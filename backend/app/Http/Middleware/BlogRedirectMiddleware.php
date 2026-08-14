<?php

namespace App\Http\Middleware;

use App\Models\BlogRedirect;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BlogRedirectMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $path = '/'.ltrim($request->getPathInfo(), '/');
        $redirect = BlogRedirect::query()
            ->where('is_active', true)
            ->where('from_path', $path)
            ->first();

        if (! $redirect) {
            return $next($request);
        }

        $redirect->increment('hits');
        $to = $redirect->to_path;
        if (! str_starts_with($to, 'http')) {
            $base = rtrim(config('app.frontend_url', config('app.url')), '/');
            $to = $base.(str_starts_with($to, '/') ? $to : '/'.$to);
        }

        return redirect()->away($to, $redirect->status_code ?: 301);
    }
}
