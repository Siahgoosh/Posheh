<?php

namespace App\Http\Controllers\Api\VirtualTour;

use App\Http\Controllers\Controller;
use App\Services\VirtualTour\VirtualTourService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VirtualTourController extends Controller
{
    public function __construct(private readonly VirtualTourService $service) {}

    public function index(Request $request): JsonResponse
    {
        $tours = $this->service->list($request->user());

        return response()->json(['data' => $tours]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'property_id' => ['nullable', 'integer', 'exists:properties,id'],
        ]);

        $tour = $this->service->create($request->user(), $data);

        return response()->json([
            'data' => $this->service->toPublicPayload($tour->load(['scenes.hotspots', 'media', 'property', 'office'])),
            'message' => 'تور مجازی ایجاد شد.',
        ], 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $tour = $this->service->findForOffice($request->user(), $id);

        return response()->json(['data' => $this->service->toPublicPayload($tour)]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'property_id' => ['nullable', 'integer'],
            'status' => ['sometimes', 'in:draft,published'],
            'settings' => ['sometimes', 'array'],
        ]);

        $tour = $this->service->update($request->user(), $id, $data);

        return response()->json([
            'data' => $this->service->toPublicPayload($tour),
            'message' => 'تور مجازی به‌روزرسانی شد.',
        ]);
    }

    public function addScene(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'default_yaw' => ['nullable', 'numeric'],
            'default_pitch' => ['nullable', 'numeric'],
            'sort_order' => ['nullable', 'integer'],
            'floor_plan_x' => ['nullable', 'numeric'],
            'floor_plan_y' => ['nullable', 'numeric'],
            'panorama_path' => ['nullable', 'string'],
        ]);

        $scene = $this->service->addScene($request->user(), $id, $data, $request->file('panorama'));

        return response()->json(['data' => $scene, 'message' => 'صحنه اضافه شد.'], 201);
    }

    public function updateScene(Request $request, int $id, int $sceneId): JsonResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string'],
            'default_yaw' => ['nullable', 'numeric'],
            'default_pitch' => ['nullable', 'numeric'],
            'sort_order' => ['nullable', 'integer'],
            'floor_plan_x' => ['nullable', 'numeric'],
            'floor_plan_y' => ['nullable', 'numeric'],
        ]);

        $scene = $this->service->updateScene($request->user(), $id, $sceneId, $data, $request->file('panorama'));

        return response()->json(['data' => $scene, 'message' => 'صحنه به‌روزرسانی شد.']);
    }

    public function deleteScene(Request $request, int $id, int $sceneId): JsonResponse
    {
        $this->service->deleteScene($request->user(), $id, $sceneId);

        return response()->json(['message' => 'صحنه حذف شد.']);
    }

    public function syncHotspots(Request $request, int $id, int $sceneId): JsonResponse
    {
        $data = $request->validate([
            'hotspots' => ['required', 'array'],
            'hotspots.*.type' => ['required', 'in:scene,info,link,video'],
            'hotspots.*.yaw' => ['required', 'numeric'],
            'hotspots.*.pitch' => ['required', 'numeric'],
            'hotspots.*.target_scene_id' => ['nullable', 'integer'],
            'hotspots.*.title' => ['nullable', 'string'],
            'hotspots.*.content' => ['nullable', 'string'],
            'hotspots.*.link_url' => ['nullable', 'string'],
            'hotspots.*.icon' => ['nullable', 'string'],
        ]);

        $scene = $this->service->syncHotspots($request->user(), $id, $sceneId, $data['hotspots']);

        return response()->json(['data' => $scene]);
    }

    public function uploadMedia(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'max:51200'],
            'type' => ['required', 'in:image,video,document'],
            'title' => ['nullable', 'string'],
        ]);

        $media = $this->service->uploadMedia(
            $request->user(),
            $id,
            $request->file('file'),
            $request->input('type'),
            $request->input('title')
        );

        return response()->json(['data' => $media, 'message' => 'فایل آپلود شد.'], 201);
    }

    public function analytics(Request $request, int $id): JsonResponse
    {
        $tour = $this->service->findForOffice($request->user(), $id);

        return response()->json([
            'data' => [
                'view_count' => $tour->view_count,
                'leads_count' => $tour->leads()->count(),
                'recent_views' => $tour->views()->latest('viewed_at')->limit(20)->get(),
                'recent_leads' => $tour->leads()->latest()->limit(10)->get(),
            ],
        ]);
    }
}
