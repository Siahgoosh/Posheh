<?php

namespace App\Http\Controllers\Api\VirtualTour;

use App\Http\Controllers\Controller;
use App\Modules\VirtualTour\Application\Services\TourAccessService;
use App\Modules\VirtualTour\Application\Services\TourAnalyticsService;
use App\Modules\VirtualTour\Application\Services\TourManager;
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

        $this->analyticsService->recordView(
            $tour,
            $request->ip(),
            $request->userAgent(),
            $request->header('referer')
        );

        $payload = $this->tourManager->toPayload($tour);
        $payload['access'] = $this->accessService->getAccessMeta($tour);

        return response()->json(['data' => $payload]);
    }

    public function verifyPassword(Request $request, string $slug): JsonResponse
    {
        $request->validate(['password' => ['required', 'string', 'max:255']]);
        $tour = $this->accessService->findForAccessCheck($slug);

        if (! $this->accessService->verifyPassword($tour, $request->input('password'))) {
            return response()->json(['message' => 'رمز دسترسی نادرست است.'], 422);
        }

        $tour = $this->accessService->resolvePublicTour($slug, $request->merge(['password' => $request->input('password')]));

        return response()->json(['data' => $this->tourManager->toPayload($tour), 'verified' => true]);
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
}
