<?php

namespace App\Modules\VirtualTour\Application\Services;

use App\Models\User;
use App\Models\VirtualTour;
use App\Models\VirtualTourScene;
use App\Modules\VirtualTour\Application\Contracts\PanoramaStorageInterface;
use App\Modules\VirtualTour\Application\Contracts\ThumbnailGeneratorInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class SceneManager
{
    public function __construct(
        private readonly TourManager $tourManager,
        private readonly PanoramaStorageInterface $storage,
        private readonly ThumbnailGeneratorInterface $thumbnailGenerator,
    ) {}

    public function create(User $user, int $tourId, array $data, ?UploadedFile $panorama = null): VirtualTourScene
    {
        $tour = $this->tourManager->findForOffice($user, $tourId);
        $sortOrder = $data['sort_order'] ?? $tour->scenes()->max('sort_order') + 1;
        $isFirst = $tour->scenes()->count() === 0;

        $path = $panorama
            ? $this->storage->store($tour->id, $panorama)
            : ($data['panorama_path'] ?? 'demo/sphere.jpg');

        $scene = $tour->scenes()->create([
            'name' => $data['name'],
            'status' => $data['status'] ?? 'draft',
            'is_default' => $isFirst,
            'is_visible' => $data['is_visible'] ?? true,
            'panorama_path' => $path,
            'default_yaw' => $data['default_yaw'] ?? 0,
            'default_pitch' => $data['default_pitch'] ?? 0,
            'sort_order' => $sortOrder,
            'floor_plan_x' => $data['floor_plan_x'] ?? null,
            'floor_plan_y' => $data['floor_plan_y'] ?? null,
            'file_size' => $panorama?->getSize(),
        ]);

        if ($panorama) {
            $this->processPanoramaMetadata($scene, $panorama);
        }

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

    public function syncHotspots(User $user, int $tourId, int $sceneId, array $hotspots): VirtualTourScene
    {
        $scene = $this->findScene($user, $tourId, $sceneId);
        $scene->hotspots()->delete();

        foreach ($hotspots as $h) {
            $scene->hotspots()->create([
                'type' => $h['type'] ?? 'scene',
                'target_scene_id' => $h['target_scene_id'] ?? null,
                'yaw' => $h['yaw'],
                'pitch' => $h['pitch'],
                'title' => $h['title'] ?? null,
                'content' => $h['content'] ?? null,
                'link_url' => $h['link_url'] ?? null,
                'icon' => $h['icon'] ?? 'arrow',
            ]);
        }

        return $scene->fresh('hotspots.targetScene');
    }

    private function findScene(User $user, int $tourId, int $sceneId): VirtualTourScene
    {
        $tour = $this->tourManager->findForOffice($user, $tourId);

        return $tour->scenes()->findOrFail($sceneId);
    }

    private function processPanoramaMetadata(VirtualTourScene $scene, UploadedFile $file): void
    {
        $imageInfo = @getimagesize($file->getRealPath());
        $updates = [];

        if ($imageInfo) {
            $updates['panorama_width'] = $imageInfo[0];
            $updates['panorama_height'] = $imageInfo[1];
        }

        $thumb = $this->thumbnailGenerator->generate($scene->panorama_path, $scene->virtual_tour_id, $scene->id);
        if ($thumb) {
            $updates['thumbnail_path'] = $thumb['thumbnail_path'];
            if (! isset($updates['panorama_width'])) {
                $updates['panorama_width'] = $thumb['width'];
                $updates['panorama_height'] = $thumb['height'];
            }
        }

        if ($updates) {
            $scene->update($updates);
        }
    }
}
