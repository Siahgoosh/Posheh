<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AnalyticsEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    public function track(Request $request): JsonResponse
    {
        $data = $request->validate([
            'event_type' => ['required', 'string', 'in:page_view,download_click'],
            'path' => ['nullable', 'string', 'max:500'],
            'referrer' => ['nullable', 'string', 'max:500'],
            'meta' => ['nullable', 'array'],
        ]);

        $ip = (string) $request->ip();
        $visitorHash = hash('sha256', $ip.'|'.($request->userAgent() ?? ''));

        AnalyticsEvent::create([
            'event_type' => $data['event_type'],
            'path' => $data['path'] ?? $request->header('Referer'),
            'referrer' => $data['referrer'] ?? $request->header('Referer'),
            'visitor_hash' => $visitorHash,
            'user_agent' => substr((string) $request->userAgent(), 0, 500),
            'user_id' => $request->user()?->id,
            'meta' => $data['meta'] ?? null,
            'created_at' => now(),
        ]);

        return response()->json(['ok' => true]);
    }
}
