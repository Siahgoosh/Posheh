<?php

namespace App\Http\Controllers\Api\VirtualTour;

use App\Http\Controllers\Controller;
use App\Modules\VirtualTour\Application\Services\TourAccessService;
use App\Modules\VirtualTour\Application\Services\TourAnalyticsService;
use App\Modules\VirtualTour\Application\Services\TourManager;
use App\Modules\VirtualTour\Application\Services\TourSeoService;
use App\Modules\VirtualTour\Application\Services\TourViewerSerializer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\GoneHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PublicVirtualTourController extends Controller
{
    public function __construct(
        private readonly TourManager $tourManager,
        private readonly TourAnalyticsService $analyticsService,
        private readonly TourAccessService $accessService,
        private readonly TourViewerSerializer $serializer,
        private readonly TourSeoService $seoService,
    ) {}

    public function show(Request $request, string $slug): JsonResponse
    {
        try {
            $tour = $this->accessService->resolvePublicTour($slug, $request);
        } catch (NotFoundHttpException) {
            return response()->json(['message' => 'تور مجازی یافت نشد.'], 404);
        } catch (GoneHttpException $e) {
            return response()->json(['message' => $e->getMessage(), 'expired' => true], 410);
        } catch (AccessDeniedHttpException $e) {
            $meta = $this->accessService->getAccessMeta(
                $this->accessService->findForAccessCheck($slug)
            );

            return response()->json([
                'message' => $e->getMessage(),
                'access' => $meta,
                'requires_password' => $meta['requires_password'],
            ], 403);
        }

        $sessionId = $request->input('session_id') ?? $request->header('X-Tour-Session');
        if (! $sessionId) {
            $sessionId = bin2hex(random_bytes(16));
        }

        $this->analyticsService->recordView(
            $tour,
            $request->ip(),
            $request->userAgent(),
            $request->header('referer'),
            $sessionId,
            [
                'device_type' => $request->input('device_type') ?? $this->detectDevice($request->userAgent()),
                'screen_width' => $request->integer('screen_width') ?: null,
                'screen_height' => $request->integer('screen_height') ?: null,
            ],
        );

        $payload = $this->serializer->serializePublic($tour);
        $payload['access'] = $this->accessService->getAccessMeta($tour);
        $payload['seo'] = $this->seoService->metaForTour($tour);
        $payload['session_id'] = $sessionId;
        $payload['security'] = [
            'disable_direct_download' => (bool) ($tour->settings['disable_direct_download'] ?? config('virtual-tour.disable_direct_download', true)),
            'watermark_enabled' => (bool) ($tour->settings['watermark_enabled'] ?? false),
            'watermark_text' => $tour->settings['watermark_text'] ?? $tour->office?->name,
        ];

        return response()->json(['data' => $payload]);
    }

    public function meta(string $slug): JsonResponse
    {
        $tour = $this->accessService->findForAccessCheck($slug);
        if ($tour->status !== 'published' || $tour->archived_at) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $tour->load(['scenes' => fn ($q) => $q->where('is_visible', true)->orderBy('sort_order'), 'property', 'office']);

        return response()->json(['data' => $this->seoService->metaForTour($tour)]);
    }

    public function recordEvents(Request $request, string $slug): JsonResponse
    {
        try {
            $tour = $this->accessService->resolvePublicTour($slug, $request);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }

        $data = $request->validate([
            'session_id' => ['required', 'string', 'max:64'],
            'events' => ['required', 'array', 'max:50'],
            'events.*.event_type' => ['required', 'string', 'max:64'],
            'events.*.scene_id' => ['nullable', 'integer'],
            'events.*.hotspot_id' => ['nullable', 'integer'],
            'events.*.position_x' => ['nullable', 'numeric'],
            'events.*.position_y' => ['nullable', 'numeric'],
            'events.*.meta' => ['nullable', 'array'],
            'duration_seconds' => ['nullable', 'integer', 'min:0'],
        ]);

        $count = $this->analyticsService->recordEvents($tour, $data['session_id'], $data['events']);

        if (isset($data['duration_seconds'])) {
            $this->analyticsService->updateSessionDuration($tour, $data['session_id'], $data['duration_seconds']);
        }

        return response()->json(['recorded' => $count]);
    }

    public function verifyPassword(Request $request, string $slug): JsonResponse
    {
        $request->validate(['password' => ['required', 'string', 'max:255']]);
        $tour = $this->accessService->findForAccessCheck($slug);

        if (! $this->accessService->verifyPassword($tour, $request->input('password'))) {
            return response()->json(['message' => 'رمز دسترسی نادرست است.'], 422);
        }

        $tour = $this->accessService->resolvePublicTour($slug, $request->merge(['password' => $request->input('password')]));

        return response()->json([
            'data' => $this->serializer->serializePublic($tour),
            'verified' => true,
        ]);
    }

    public function submitLead(Request $request, string $slug): JsonResponse
    {
        try {
            $tour = $this->accessService->resolvePublicTour($slug, $request);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'mobile' => ['required', 'string', 'max:15'],
            'message' => ['nullable', 'string', 'max:2000'],
        ]);

        $lead = $this->analyticsService->submitLead($tour, $data);

        return response()->json([
            'data' => $lead,
            'message' => 'درخواست شما ثبت شد. به زودی با شما تماس می‌گیریم.',
        ], 201);
    }

    private function detectDevice(?string $ua): string
    {
        if (! $ua) {
            return 'unknown';
        }
        $ua = strtolower($ua);
        if (str_contains($ua, 'mobile') || str_contains($ua, 'android') || str_contains($ua, 'iphone')) {
            return 'mobile';
        }
        if (str_contains($ua, 'tablet') || str_contains($ua, 'ipad')) {
            return 'tablet';
        }
        if (str_contains($ua, 'tv') || str_contains($ua, 'smart-tv')) {
            return 'tv';
        }

        return 'desktop';
    }
}
