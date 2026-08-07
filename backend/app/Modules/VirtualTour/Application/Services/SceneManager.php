<?php

namespace App\Modules\VirtualTour\Application\Services;

use App\Models\User;
use App\Models\VirtualTour;
use App\Models\VirtualTourScene;
use App\Modules\VirtualTour\Application\Contracts\PanoramaStorageInterface;
use App\Modules\VirtualTour\Application\Contracts\ThumbnailGeneratorInterface;
use App\Modules\VirtualTour\Domain\SceneType;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class SceneManager
{
    public function __construct(
        private readonly TourManager $tourManager,
        private readonly PanoramaStorageInterface $storage,
        private readonly ThumbnailGeneratorInterface $thumbnailGenerator,
        private readonly ImageVariantService $imageVariantService,
    ) {}

    public function create(User $user, int $tourId, array $data, ?UploadedFile $panorama = null): VirtualTourScene
    {
        $tour = $this->tourManager->findForOffice($user, $tourId);
        $sortOrder = $data['sort_order'] ?? (($tour->scenes()->max('sort_order') ?? -1) + 1);
        $isFirst = $tour->scenes()->count() === 0;

        $path = $panorama
            ? $this->storage->store($tour->id, $panorama)
            : ($data['panorama_path'] ?? 'demo/sphere.jpg');

        $scene = $tour->scenes()->create($this->buildSceneAttributes($data, $path, $panorama, $sortOrder, $isFirst));

        if ($panorama) {
            $this->processPanoramaMetadata($scene, $panorama);
        }

        return $scene->fresh('hotspots');
    }

    public function createFlatImage(User $user, int $tourId, array $data, UploadedFile $image): VirtualTourScene
    {
        $tour = $this->tourManager->findForOffice($user, $tourId);
        $sortOrder = $data['sort_order'] ?? (($tour->scenes()->max('sort_order') ?? -1) + 1);
        $isFirst = $tour->scenes()->count() === 0;

        $path = $this->storage->store($tour->id, $image, 'scenes');

        $scene = $tour->scenes()->create(array_merge(
            $this->buildSceneAttributes($data, $path, $image, $sortOrder, $isFirst),
            [
                'scene_type' => SceneType::FlatImage->value,
            ]
        ));

        $this->processFlatImageMetadata($scene, $image);

        return $scene->fresh('hotspots');
    }

    public function updateFlatImage(
        User $user,
        int $tourId,
        int $sceneId,
        array $data,
        UploadedFile $image,
    ): VirtualTourScene {
        $scene = $this->findScene($user, $tourId, $sceneId);

        $this->deleteSceneAssets($scene);

        $path = $this->storage->store($tourId, $image, 'scenes');
        $data['panorama_path'] = $path;
        $data['file_size'] = $image->getSize();
        $data['scene_type'] = SceneType::FlatImage->value;

        $scene->update($data);
        $this->processFlatImageMetadata($scene->fresh(), $image);

        return $scene->fresh('hotspots');
    }

    public function update(User $user, int $tourId, int $sceneId, array $data, ?UploadedFile $panorama = null): VirtualTourScene
    {
        $scene = $this->findScene($user, $tourId, $sceneId);

        if ($panorama) {
            $this->storage->delete($scene->panorama_path);
            if ($scene->thumbnail_path) {
                $this->storage->delete($scene->thumbnail_path);
            }

            $path = $this->storage->store($tourId, $panorama);
            $data['panorama_path'] = $path;
            $data['file_size'] = $panorama->getSize();
        }

        $scene->update($data);

        if ($panorama) {
            $this->processPanoramaMetadata($scene->fresh(), $panorama);
        }

        return $scene->fresh('hotspots');
    }

    public function delete(User $user, int $tourId, int $sceneId): void
    {
        $scene = $this->findScene($user, $tourId, $sceneId);
        $wasDefault = $scene->is_default;

        $this->storage->delete($scene->panorama_path);
        if ($scene->thumbnail_path) {
            $this->storage->delete($scene->thumbnail_path);
        }
        if ($scene->image_variants) {
            $this->imageVariantService->deleteVariants($scene->image_variants);
        }

        $scene->delete();

        if ($wasDefault) {
            $tour = VirtualTour::find($tourId);
            $next = $tour?->scenes()->orderBy('sort_order')->first();
            $next?->update(['is_default' => true]);
        }
    }

    public function duplicate(User $user, int $tourId, int $sceneId): VirtualTourScene
    {
        $scene = $this->findScene($user, $tourId, $sceneId);
        $tour = $scene->tour;

        $copy = $scene->replicate();
        $copy->name = $scene->name.' (کپی)';
        $copy->is_default = false;
        $copy->status = 'draft';
        $copy->sort_order = ($tour->scenes()->max('sort_order') ?? 0) + 1;

        if (! str_starts_with($scene->panorama_path, 'demo/')) {
            $disk = \Illuminate\Support\Facades\Storage::disk('public');
            if ($disk->exists($scene->panorama_path)) {
                $ext = pathinfo($scene->panorama_path, PATHINFO_EXTENSION) ?: 'jpg';
                $newPath = "virtual-tours/{$tourId}/panoramas/".Str::uuid().".{$ext}";
                $disk->copy($scene->panorama_path, $newPath);
                $copy->panorama_path = $newPath;
            }
            if ($scene->thumbnail_path && $disk->exists($scene->thumbnail_path)) {
                $newThumb = "virtual-tours/{$tourId}/thumbnails/".Str::uuid().'.jpg';
                $disk->copy($scene->thumbnail_path, $newThumb);
                $copy->thumbnail_path = $newThumb;
            }
        }

        $copy->save();

        return $copy->fresh('hotspots');
    }

    public function reorder(User $user, int $tourId, array $sceneIds): void
    {
        $tour = $this->tourManager->findForOffice($user, $tourId);
        $validIds = $tour->scenes()->pluck('id')->all();

        foreach ($sceneIds as $index => $sceneId) {
            if (! in_array($sceneId, $validIds, true)) {
                continue;
            }
            VirtualTourScene::where('id', $sceneId)->update(['sort_order' => $index]);
        }
    }

    public function publish(User $user, int $tourId, int $sceneId): VirtualTourScene
    {
        $scene = $this->findScene($user, $tourId, $sceneId);
        $scene->update(['status' => 'published']);

        return $scene->fresh('hotspots');
    }

    public function unpublish(User $user, int $tourId, int $sceneId): VirtualTourScene
    {
        $scene = $this->findScene($user, $tourId, $sceneId);
        $scene->update(['status' => 'draft']);

        return $scene->fresh('hotspots');
    }

    public function setDefault(User $user, int $tourId, int $sceneId): VirtualTourScene
    {
        $tour = $this->tourManager->findForOffice($user, $tourId);
        $tour->scenes()->update(['is_default' => false]);

        $scene = $this->findScene($user, $tourId, $sceneId);
        $scene->update(['is_default' => true, 'is_visible' => true]);

        return $scene->fresh('hotspots');
    }
    private function findScene(User $user, int $tourId, int $sceneId): VirtualTourScene
    {
        $tour = $this->tourManager->findForOffice($user, $tourId);

        return $tour->scenes()->findOrFail($sceneId);
    }

    private function isCrediblePanoramaDimensions(int $width, int $height): bool
    {
        return $width >= 512 && $height >= 256;
    }

    private function processPanoramaMetadata(VirtualTourScene $scene, UploadedFile $file): void
    {
        try {
            $imageInfo = @getimagesize($file->getRealPath());
            $updates = [];

            if ($imageInfo) {
                if ($this->hasSceneColumn('panorama_width')) {
                    $updates['panorama_width'] = $imageInfo[0];
                }
                if ($this->hasSceneColumn('panorama_height')) {
                    $updates['panorama_height'] = $imageInfo[1];
                }
            }

            $thumb = $this->thumbnailGenerator->generate($scene->panorama_path, $scene->virtual_tour_id, $scene->id);
            if ($thumb) {
                if ($this->hasSceneColumn('thumbnail_path')) {
                    $updates['thumbnail_path'] = $thumb['thumbnail_path'];
                }
            }

            // Never store thumbnail dimensions as panorama size — tiny values break 360 zoom / Smart Walk layout.
            if (isset($updates['panorama_width'], $updates['panorama_height'])
                && ! $this->isCrediblePanoramaDimensions($updates['panorama_width'], $updates['panorama_height'])) {
                unset($updates['panorama_width'], $updates['panorama_height']);
            }

            if ($updates) {
                $scene->update($updates);
            }
        } catch (\Throwable $e) {
            Log::warning('virtual-tour.panorama_metadata_failed', [
                'scene_id' => $scene->id,
                'message' => $e->getMessage(),
            ]);
        }
    }

  /** @return array<string, mixed> */
    private function buildSceneAttributes(
        array $data,
        string $path,
        ?UploadedFile $panorama,
        int $sortOrder,
        bool $isFirst,
    ): array {
        $attrs = [
            'name' => Str::limit($data['name'] ?? 'صحنه جدید', 200),
            'panorama_path' => $path,
            'default_yaw' => $data['default_yaw'] ?? 0,
            'default_pitch' => $data['default_pitch'] ?? 0,
            'sort_order' => $sortOrder,
            'floor_plan_x' => $data['floor_plan_x'] ?? null,
            'floor_plan_y' => $data['floor_plan_y'] ?? null,
        ];

        if ($this->hasSceneColumn('status')) {
            $attrs['status'] = $data['status'] ?? 'draft';
        }
        if ($this->hasSceneColumn('is_default')) {
            $attrs['is_default'] = $data['is_default'] ?? $isFirst;
        }
        if ($this->hasSceneColumn('is_visible')) {
            $attrs['is_visible'] = $data['is_visible'] ?? true;
        }
        if ($this->hasSceneColumn('file_size') && $panorama) {
            $attrs['file_size'] = $panorama->getSize();
        }
        if ($this->hasSceneColumn('scene_type')) {
            $attrs['scene_type'] = $data['scene_type'] ?? SceneType::Equirectangular->value;
        }

        return $attrs;
    }

    private function processFlatImageMetadata(VirtualTourScene $scene, UploadedFile $file): void
    {
        try {
            $disk = \Illuminate\Support\Facades\Storage::disk('public');
            $fullPath = $disk->path($scene->panorama_path);
            $imageInfo = @getimagesize($fullPath);
            $updates = [];

            if ($imageInfo) {
                if ($this->hasSceneColumn('panorama_width')) {
                    $updates['panorama_width'] = $imageInfo[0];
                }
                if ($this->hasSceneColumn('panorama_height')) {
                    $updates['panorama_height'] = $imageInfo[1];
                }
            }

            if (isset($updates['panorama_width'], $updates['panorama_height'])
                && ! $this->isCrediblePanoramaDimensions($updates['panorama_width'], $updates['panorama_height'])) {
                unset($updates['panorama_width'], $updates['panorama_height']);
            }

            $variantData = $this->imageVariantService->generateVariants(
                $fullPath,
                $scene->virtual_tour_id,
                $scene->id
            );

            if ($variantData) {
                $urls = [
                    'original' => $scene->panorama_path,
                    'width' => $variantData['width'],
                    'height' => $variantData['height'],
                    'format' => $variantData['format'],
                ];

                foreach (['thumbnail', 'medium', 'large', 'ultra'] as $key) {
                    if (! empty($variantData[$key])) {
                        $urls[$key] = $variantData[$key];
                    }
                }

                if ($this->hasSceneColumn('image_variants')) {
                    $updates['image_variants'] = $urls;
                }
                if (! empty($variantData['thumbnail']) && $this->hasSceneColumn('thumbnail_path')) {
                    $updates['thumbnail_path'] = $variantData['thumbnail'];
                }
            }

            if ($updates) {
                $scene->update($updates);
            }
        } catch (\Throwable $e) {
            Log::warning('virtual-tour.flat_image_metadata_failed', [
                'scene_id' => $scene->id,
                'message' => $e->getMessage(),
            ]);
        }
    }

    private function deleteSceneAssets(VirtualTourScene $scene): void
    {
        $this->storage->delete($scene->panorama_path);
        if ($scene->thumbnail_path) {
            $this->storage->delete($scene->thumbnail_path);
        }
        if ($scene->image_variants) {
            $this->imageVariantService->deleteVariants($scene->image_variants);
        }
    }

    private function hasSceneColumn(string $column): bool
    {
        static $columns = null;
        if ($columns === null) {
            $columns = Schema::getColumnListing('virtual_tour_scenes');
        }

        return in_array($column, $columns, true);
    }
}
