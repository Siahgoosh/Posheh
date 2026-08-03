<?php

namespace App\Http\Controllers\Api\VirtualTour;

use App\Http\Controllers\Controller;
use App\Modules\VirtualTour\Application\Services\TourAnalyticsService;
use App\Modules\VirtualTour\Application\Services\TourManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicVirtualTourController extends Controller
{
    public function __construct(
        private readonly TourManager $tourManager,
        private readonly TourAnalyticsService $analyticsService,
    ) {}

    public function show(Request $request, string $slug): JsonResponse
    {
        $tour = $this->tourManager->findPublic($slug);
        $this->analyticsService->recordView(
            $tour,
            $request->ip(),
            $request->userAgent(),
            $request->header('referer')
        );

        return response()->json(['data' => $this->tourManager->toPayload($tour)]);
    }

    public function submitLead(Request $request, string $slug): JsonResponse
    {
        $tour = $this->tourManager->findPublic($slug);
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
