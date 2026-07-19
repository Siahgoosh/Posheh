<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ActivityResource;
use App\Models\Activity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $activities = Activity::where('office_id', $request->user()->office_id)
            ->with('user')
            ->latest()
            ->paginate(50);

        return response()->json([
            'data' => ActivityResource::collection($activities),
            'meta' => [
                'current_page' => $activities->currentPage(),
                'last_page' => $activities->lastPage(),
                'total' => $activities->total(),
            ],
        ]);
    }
}
