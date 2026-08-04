<?php

namespace App\Http\Controllers\Api\VirtualTour;

use App\Http\Controllers\Controller;
use App\Models\VirtualTour;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Future AI integration endpoints — scaffolding for automated tour enhancement.
 */
class VirtualTourAiController extends Controller
{
    public function capabilities(): JsonResponse
    {
        return response()->json([
            'data' => [
                'hotspot_suggestions' => ['status' => 'planned', 'endpoint' => 'POST /virtual-tours/{id}/ai/hotspot-suggestions'],
                'room_recognition' => ['status' => 'planned'],
                'door_detection' => ['status' => 'planned'],
                'window_detection' => ['status' => 'planned'],
                'scene_ordering' => ['status' => 'planned'],
                'property_description' => ['status' => 'planned'],
                'voice_narration' => ['status' => 'planned'],
                'thumbnail_generation' => ['status' => 'planned'],
                'cover_image_selection' => ['status' => 'planned'],
            ],
        ]);
    }

    public function hotspotSuggestions(Request $request, int $id): JsonResponse
    {
        $tour = $this->findTour($request, $id);

        return response()->json([
            'data' => [],
            'message' => 'AI hotspot suggestions will be available in a future release.',
            'tour_id' => $tour->id,
            'status' => 'not_implemented',
        ], 202);
    }

    public function sceneOrdering(Request $request, int $id): JsonResponse
    {
        $tour = $this->findTour($request, $id);

        return response()->json([
            'data' => ['ordered_scene_ids' => $tour->scenes->pluck('id')],
            'message' => 'AI scene ordering placeholder — returns current order.',
            'status' => 'stub',
        ]);
    }

    public function generateDescription(Request $request, int $id): JsonResponse
    {
        $this->findTour($request, $id);

        return response()->json([
            'data' => ['description' => null],
            'message' => 'AI property description generation — coming soon.',
            'status' => 'not_implemented',
        ], 202);
    }

    public function generateNarration(Request $request, int $id): JsonResponse
    {
        $this->findTour($request, $id);

        return response()->json([
            'data' => ['audio_url' => null],
            'message' => 'AI voice narration — coming soon.',
            'status' => 'not_implemented',
        ], 202);
    }

    private function findTour(Request $request, int $id): VirtualTour
    {
        return VirtualTour::where('office_id', $request->user()->office_id)->findOrFail($id);
    }
}
