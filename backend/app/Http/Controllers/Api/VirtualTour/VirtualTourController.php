<?php

namespace App\Http\Controllers\Api\VirtualTour;

use App\Http\Controllers\Controller;
use App\Modules\VirtualTour\Application\Services\HotspotManager;
use App\Modules\VirtualTour\Application\Services\HotspotSerializer;
use App\Modules\VirtualTour\Application\Services\PanoramaUploader;
use App\Modules\VirtualTour\Application\Services\SceneImageUploader;
use App\Modules\VirtualTour\Application\Services\TourAnalyticsService;
use App\Modules\VirtualTour\Application\Services\TourManager;
use App\Modules\VirtualTour\Application\Services\TourViewerSerializer;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VirtualTourController extends Controller
{
    private const HOTSPOT_TYPES = [
        'scene', 'info', 'gallery', 'image', 'video', 'audio', 'pdf',
        'website', 'whatsapp', 'telegram', 'phone', 'email', 'maps',
        'floor_plan', 'custom', 'link',
    ];

    public function __construct(
        private readonly TourManager $tourManager,
        private readonly SceneManager $sceneManager,
        private readonly HotspotManager $hotspotManager,
        private readonly HotspotSerializer $hotspotSerializer,
        private readonly PanoramaUploader $panoramaUploader,
        private readonly SceneImageUploader $sceneImageUploader,
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
            'tour_type' => ['nullable', 'in:panorama_360,smart_walk'],
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
            'status' => ['sometimes', 'in:draft,published,archived'],
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
            'default_fov' => ['nullable', 'integer', 'min:30', 'max:120'],
            'background_music' => ['nullable', 'string'],
            'ambient_sound' => ['nullable', 'string'],
            'transition_effect' => ['nullable', 'in:fade,crossfade,none'],
            'scene_settings' => ['nullable', 'array'],
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

    public function uploadSceneImage(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'image' => ['required', 'file', 'mimes:jpeg,jpg,png,webp,avif', 'max:51200'],
            'name' => ['nullable', 'string', 'max:255'],
            'scene_id' => ['nullable', 'integer'],
        ]);

        try {
            $scene = $this->sceneImageUploader->uploadSceneImage(
                $request->user(),
                $id,
                $request->file('image'),
                $request->input('name'),
                $request->integer('scene_id') ?: null,
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            Log::error('virtual-tour.scene_image.upload_failed', [
                'tour_id' => $id,
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => config('app.debug')
                    ? $e->getMessage()
                    : 'خطا در آپلود تصویر. لطفاً دوباره تلاش کنید.',
            ], 500);
        }

        return response()->json([
            'data' => $this->serializer->serializeScene($scene),
            'message' => 'تصویر صحنه آپلود شد.',
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
            'default_fov' => ['nullable', 'integer', 'min:30', 'max:120'],
            'background_music' => ['nullable', 'string'],
            'ambient_sound' => ['nullable', 'string'],
            'transition_effect' => ['nullable', 'in:fade,crossfade,none'],
            'scene_settings' => ['nullable', 'array'],
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
            'panorama' => ['required', 'file', 'mimes:jpeg,jpg,png,webp', 'max:102400'],
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
        } catch (QueryException $e) {
            if (str_contains($e->getMessage(), 'Unknown column')) {
                return response()->json([
                    'message' => 'دیتابیس به‌روز نیست. لطفاً php artisan migrate --force را اجرا کنید.',
                    'code' => 'schema_outdated',
                ], 503);
            }
            Log::error('virtual-tour.upload.db_error', ['message' => $e->getMessage()]);
            throw $e;
        } catch (\Throwable $e) {
            Log::error('virtual-tour.upload.failed', [
                'tour_id' => $id,
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => config('app.debug')
                    ? $e->getMessage()
                    : 'خطا در آپلود پانوراما. لطفاً دوباره تلاش کنید.',
            ], 500);
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
            'hotspots.*.type' => ['required', 'in:'.implode(',', self::HOTSPOT_TYPES)],
            'hotspots.*.yaw' => ['nullable', 'numeric'],
            'hotspots.*.pitch' => ['nullable', 'numeric'],
            'hotspots.*.position_x' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'hotspots.*.position_y' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'hotspots.*.position_z' => ['nullable', 'numeric'],
            'hotspots.*.target_scene_id' => ['nullable', 'integer'],
            'hotspots.*.title' => ['nullable', 'string'],
            'hotspots.*.label' => ['nullable', 'string'],
            'hotspots.*.tooltip' => ['nullable', 'string'],
            'hotspots.*.content' => ['nullable', 'string'],
            'hotspots.*.link_url' => ['nullable', 'string'],
            'hotspots.*.icon' => ['nullable', 'string'],
            'hotspots.*.style' => ['nullable', 'array'],
            'hotspots.*.action' => ['nullable', 'array'],
            'hotspots.*.popup' => ['nullable', 'array'],
            'hotspots.*.sort_order' => ['nullable', 'integer'],
        ]);

        $scene = $this->hotspotManager->sync($request->user(), $id, $sceneId, $data['hotspots']);

        return response()->json(['data' => $this->serializer->serializeScene($scene)]);
    }

    public function addHotspot(Request $request, int $id, int $sceneId): JsonResponse
    {
        $data = $request->validate($this->hotspotRules(requirePosition: true));

        $hotspot = $this->hotspotManager->create($request->user(), $id, $sceneId, $data);

        return response()->json([
            'data' => $this->hotspotSerializer->serialize($hotspot),
            'message' => 'هات‌اسپات اضافه شد.',
        ], 201);
    }

    public function updateHotspot(Request $request, int $id, int $sceneId, int $hotspotId): JsonResponse
    {
        $data = $request->validate($this->hotspotRules(requirePosition: false));

        $hotspot = $this->hotspotManager->update($request->user(), $id, $sceneId, $hotspotId, $data);

        return response()->json([
            'data' => $this->hotspotSerializer->serialize($hotspot),
        ]);
    }

    public function deleteHotspot(Request $request, int $id, int $sceneId, int $hotspotId): JsonResponse
    {
        $this->hotspotManager->delete($request->user(), $id, $sceneId, $hotspotId);

        return response()->json(['message' => 'هات‌اسپات حذف شد.']);
    }

    /** @return array<string, mixed> */
    private function hotspotRules(bool $requirePosition): array
    {
        $rules = [
            'type' => ['sometimes', 'in:'.implode(',', self::HOTSPOT_TYPES)],
            'target_scene_id' => ['nullable', 'integer'],
            'title' => ['nullable', 'string', 'max:255'],
            'label' => ['nullable', 'string', 'max:255'],
            'tooltip' => ['nullable', 'string', 'max:500'],
            'content' => ['nullable', 'string'],
            'link_url' => ['nullable', 'string'],
            'icon' => ['nullable', 'string', 'max:50'],
            'style' => ['nullable', 'array'],
            'action' => ['nullable', 'array'],
            'popup' => ['nullable', 'array'],
            'sort_order' => ['nullable', 'integer'],
        ];

        if ($requirePosition) {
            $rules['type'] = ['required', 'in:'.implode(',', self::HOTSPOT_TYPES)];
            $rules['yaw'] = ['nullable', 'numeric'];
            $rules['pitch'] = ['nullable', 'numeric'];
            $rules['position_x'] = ['nullable', 'numeric', 'min:0', 'max:100'];
            $rules['position_y'] = ['nullable', 'numeric', 'min:0', 'max:100'];
            $rules['position_z'] = ['nullable', 'numeric'];
        } else {
            $rules['yaw'] = ['sometimes', 'numeric'];
            $rules['pitch'] = ['sometimes', 'numeric'];
            $rules['position_x'] = ['sometimes', 'numeric', 'min:0', 'max:100'];
            $rules['position_y'] = ['sometimes', 'numeric', 'min:0', 'max:100'];
            $rules['position_z'] = ['sometimes', 'numeric'];
        }

        return $rules;
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
