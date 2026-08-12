<?php

namespace App\Http\Controllers\Api\Cro;

use App\Http\Controllers\Controller;
use App\Services\Cro\CroConversionTracker;
use App\Services\Cro\CroCtaResolver;
use App\Services\Cro\CroLeadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class CroPublicController extends Controller
{
    public function __construct(
        private readonly CroLeadService $leads,
        private readonly CroConversionTracker $tracker,
        private readonly CroCtaResolver $ctas,
    ) {}

    public function resolveCta(Request $request): JsonResponse
    {
        $cta = $this->ctas->resolve($request->validate([
            'category' => ['nullable', 'string', 'max:100'],
            'topic' => ['nullable', 'string', 'max:100'],
            'intent' => ['nullable', 'string', 'max:50'],
            'funnel_stage' => ['nullable', 'string', 'max:20'],
            'slug' => ['nullable', 'string', 'max:200'],
            'path' => ['nullable', 'string', 'max:500'],
            'cta_key' => ['nullable', 'string', 'max:100'],
        ]));

        return response()->json(['data' => $cta]);
    }

    public function track(Request $request): JsonResponse
    {
        $data = $request->validate([
            'event_type' => ['required', 'string', 'max:50'],
            'path' => ['nullable', 'string', 'max:500'],
            'article_slug' => ['nullable', 'string', 'max:200'],
            'cro_cta_id' => ['nullable', 'integer'],
            'session_id' => ['nullable', 'string', 'max:64'],
            'meta' => ['nullable', 'array'],
        ]);
        $visitorHash = hash('sha256', ((string) $request->ip()).'|'.((string) $request->userAgent()).'|'.(string) config('app.key'));
        $this->tracker->track($data['event_type'], [
            ...$data,
            'visitor_hash' => $visitorHash,
        ]);

        return response()->json(['ok' => true]);
    }

    public function captureLead(Request $request): JsonResponse
    {
        $key = 'cro-lead:'.($request->ip() ?? 'unknown');
        $limit = (int) config('cro.form.rate_limit_per_ip', 5);
        if (RateLimiter::tooManyAttempts($key, $limit)) {
            return response()->json(['message' => 'تعداد درخواست‌ها زیاد است. کمی بعد تلاش کنید.'], 429);
        }
        RateLimiter::hit($key, 3600);

        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:120'],
            'mobile' => ['required', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:190'],
            'request_type' => ['nullable', 'string', 'max:40'],
            'property_type' => ['nullable', 'string', 'max:80'],
            'city' => ['nullable', 'string', 'max:80'],
            'location' => ['nullable', 'string', 'max:120'],
            'budget' => ['nullable', 'string', 'max:80'],
            'message' => ['nullable', 'string', 'max:2000'],
            'source' => ['nullable', 'string', 'max:40'],
            'article_slug' => ['nullable', 'string', 'max:200'],
            'article_url' => ['nullable', 'string', 'max:500'],
            'category_slug' => ['nullable', 'string', 'max:100'],
            'landing_page' => ['nullable', 'string', 'max:500'],
            'first_touch_path' => ['nullable', 'string', 'max:500'],
            'last_touch_path' => ['nullable', 'string', 'max:500'],
            'conversion_page' => ['nullable', 'string', 'max:500'],
            'utm_source' => ['nullable', 'string', 'max:120'],
            'utm_medium' => ['nullable', 'string', 'max:120'],
            'utm_campaign' => ['nullable', 'string', 'max:120'],
            'utm_content' => ['nullable', 'string', 'max:120'],
            'gclid' => ['nullable', 'string', 'max:120'],
            'keyword' => ['nullable', 'string', 'max:190'],
            'campaign' => ['nullable', 'string', 'max:120'],
            'consent' => ['nullable', 'boolean'],
            'intent' => ['nullable', 'string', 'max:40'],
            'form_variant' => ['nullable', 'string', 'max:40'],
            'website' => ['nullable', 'string', 'max:200'], // honeypot
        ]);

        $result = $this->leads->capture($data, $request->ip(), $request->userAgent());

        return response()->json([
            'message' => 'درخواست شما ثبت شد.',
            'data' => [
                'uuid' => $result['lead']->uuid,
                'duplicate' => $result['duplicate'],
                'lead_score' => $result['lead']->lead_score,
                // never echo full PII beyond ack
            ],
        ], 201);
    }
}
