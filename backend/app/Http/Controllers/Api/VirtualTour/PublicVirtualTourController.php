<?php

namespace App\Http\Controllers\Api\VirtualTour;

use App\Http\Controllers\Controller;
use App\Services\VirtualTour\VirtualTourService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicVirtualTourController extends Controller
{
    public function __construct(private readonly VirtualTourService $service) {}

    public function show(Request $request, string $slug): JsonResponse
    {
        $tour = $this->service->findPublic($slug);
        $this->service->recordView(
            $tour,
            $request->ip(),
            $request->userAgent(),
            $request->header('referer')
        );

        return response()->json(['data' => $this->service->toPublicPayload($tour)]);
    }

    public function submitLead(Request $request, string $slug): JsonResponse
    {
        $tour = $this->service->findPublic($slug);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'mobile' => ['required', 'string', 'max:15'],
            'message' => ['nullable', 'string', 'max:2000'],
        ]);

        $lead = $this->service->submitLead($tour, $data);

        return response()->json([
            'data' => $lead,
            'message' => 'درخواست شما ثبت شد. به زودی با شما تماس می‌گیریم.',
        ], 201);
    }
}
