<?php

namespace App\Http\Controllers\Api\VirtualTour;

use App\Http\Controllers\Controller;
use App\Modules\VirtualTour\Application\Services\PanoramaUploader;
use App\Modules\VirtualTour\Application\Services\SceneManager;
use App\Modules\VirtualTour\Application\Services\TourAnalyticsService;
use App\Modules\VirtualTour\Application\Services\TourManager;
use App\Modules\VirtualTour\Application\Services\TourViewerSerializer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VirtualTourController extends Controller
{
    public function __construct(
        private readonly TourManager $tourManager,
        private readonly SceneManager $sceneManager,
        private readonly PanoramaUploader $panoramaUploader,
        private readonly TourAnalyticsService $analyticsService,
        private readonly TourViewerSerializer $serializer,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tours = $this->tourManager->list($request->user());

        return response()->json(['data' => $tours]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'property_id' => ['nullable', 'integer', 'exists:properties,id'],
        ]);

        $tour = $this->tourManager->create($request->user(), $data);

        return response()->json([
            'data' => $this->tourManager->toPayload($tour->load(['scenes.hotspots', 'media', 'property', 'office'])),
            'message' => 'تور مجازی ایجاد شد.',
        ], 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $tour = $this->tourManager->findForOffice($request->user(), $id);

        return response()->json(['data' => $this->tourManager->toPayload($tour)]);
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

        $tour = $this->tourManager->update($request->user(), $id, $data);

        return response()->json([
            'data' => $this->tourManager->toPayload($tour),
            'message' => 'تور مجازی به‌روزرسانی شد.',
        ]);
    }

    public function addScene(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'status' => ['nullable', 'in:draft,published'],
            'is_visible' => ['nullable', 'boolean'],
            'default_yaw' => ['nullable', 'numeric'],
            'default_pitch' => ['nullable', 'numeric'],
            'sort_order' => ['nullable', 'integer'],
            'floor_plan_x' => ['nullable', 'numeric'],
            'floor_plan_y' => ['nullable', 'numeric'],
            'panorama_path' => ['nullable', 'string'],
        ]);

        $scene = $this->sceneManager->create($request->user(), $id, $data, $request->file('panorama'));

        return response()->json([
            'data' => $this->serializer->serializeScene($scene),
            'message' => 'صحنه اضافه شد.',
        ], 201);
    }

    public function updateScene(Request $request, int $id, int $sceneId): JsonResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string'],
            'status' => ['sometimes', 'in:draft,published'],
            'is_visible' => ['sometimes', 'boolean'],
            'default_yaw' => ['nullable', 'numeric'],
            'default_pitch' => ['nullable', 'numeric'],
            'sort_order' => ['nullable', 'integer'],
            'floor_plan_x' => ['nullable', 'numeric'],
            'floor_plan_y' => ['nullable', 'numeric'],
        ]);

        $scene = $this->sceneManager->update(
            $request->user(),
            $id,
            $sceneId,
            $data,
            $request->file('panorama')
        );

        return response()->json(['data' => $this->serializer->serializeScene($scene)]);
    }

    public function deleteScene(Request $request, int $id, int $sceneId): JsonResponse
    {
        $this->sceneManager->delete($request->user(), $id, $sceneId);

        return response()->json(['message' => 'صحنه حذف شد.']);
    }

    public function duplicateScene(Request $request, int $id, int $sceneId): JsonResponse
    {
        $scene = $this->sceneManager->duplicate($request->user(), $id, $sceneId);

        return response()->json([
            'data' => $this->serializer->serializeScene($scene),
            'message' => 'صحنه کپی شد.',
        ], 201);
    }

    public function reorderScenes(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'scene_ids' => ['required', 'array'],
            'scene_ids.*' => ['integer'],
        ]);

        $this->sceneManager->reorder($request->user(), $id, $data['scene_ids']);

        $tour = $this->tourManager->findForOffice($request->user(), $id);

        return response()->json([
            'data' => $this->tourManager->toPayload($tour),
            'message' => 'ترتیب صحنه‌ها به‌روزرسانی شد.',
        ]);
    }

    public function publishScene(Request $request, int $id, int $sceneId): JsonResponse
    {
        $scene = $this->sceneManager->publish($request->user(), $id, $sceneId);

        return response()->json([
            'data' => $this->serializer->serializeScene($scene),
            'message' => 'صحنه منتشر شد.',
        ]);
    }

    public function unpublishScene(Request $request, int $id, int $sceneId): JsonResponse
    {
        $scene = $this->sceneManager->unpublish($request->user(), $id, $sceneId);

        return response()->json([
            'data' => $this->serializer->serializeScene($scene),
            'message' => 'صحنه به پیش‌نویس تغییر کرد.',
        ]);
    }

    public function setDefaultScene(Request $request, int $id, int $sceneId): JsonResponse
    {
        $scene = $this->sceneManager->setDefault($request->user(), $id, $sceneId);

        return response()->json([
            'data' => $this->serializer->serializeScene($scene),
            'message' => 'صحنه پیش‌فرض تنظیم شد.',
        ]);
    }

    public function uploadPanorama(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'panorama' => ['required', 'file', 'max:102400'],
            'name' => ['nullable', 'string', 'max:255'],
            'scene_id' => ['nullable', 'integer'],
        ]);

        try {
            $scene = $this->panoramaUploader->uploadScenePanorama(
                $request->user(),
                $id,
                $request->file('panorama'),
                $request->input('name'),
                $request->integer('scene_id') ?: null,
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'data' => $this->serializer->serializeScene($scene),
            'message' => 'پانوراما آپلود شد.',
        ], 201);
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

        $scene = $this->sceneManager->syncHotspots($request->user(), $id, $sceneId, $data['hotspots']);

        return response()->json(['data' => $this->serializer->serializeScene($scene)]);
    }

    public function uploadMedia(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'max:51200'],
            'type' => ['required', 'in:image,video,document'],
            'title' => ['nullable', 'string'],
        ]);

        $media = $this->panoramaUploader->uploadMedia(
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
        return response()->json([
            'data' => $this->analyticsService->getAnalytics($request->user(), $id),
        ]);
    }
}
