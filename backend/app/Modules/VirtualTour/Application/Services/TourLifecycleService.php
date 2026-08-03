<?php

namespace App\Modules\VirtualTour\Application\Services;

use App\Models\User;
use App\Models\VirtualTour;
use App\Models\VirtualTourHotspot;
use App\Models\VirtualTourScene;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TourLifecycleService
{
    public function __construct(
        private readonly TourActivityLogger $logger,
        private readonly TourVersionService $versionService,
    ) {}

    public function publish(User $user, VirtualTour $tour): VirtualTour
    {
        $tour->update([
            'status' => 'published',
            'published_at' => now(),
            'archived_at' => null,
        ]);
        $this->versionService->createSnapshot($user, $tour, 'انتشار');
        $this->logger->log($tour, 'tour.published', $user);

        return $tour->fresh();
    }

    public function unpublish(User $user, VirtualTour $tour): VirtualTour
    {
        $tour->update(['status' => 'draft']);
        $this->logger->log($tour, 'tour.unpublished', $user);

        return $tour->fresh();
    }

    public function archive(User $user, VirtualTour $tour): VirtualTour
    {
        $tour->update([
            'status' => 'archived',
            'archived_at' => now(),
        ]);
        $this->logger->log($tour, 'tour.archived', $user);

        return $tour->fresh();
    }

    public function restoreFromArchive(User $user, VirtualTour $tour): VirtualTour
    {
        $tour->update([
            'status' => 'draft',
            'archived_at' => null,
        ]);
        $this->logger->log($tour, 'tour.unarchived', $user);

        return $tour->fresh();
    }

    public function duplicate(User $user, VirtualTour $tour): VirtualTour
    {
        return DB::transaction(function () use ($user, $tour) {
            $tour->load(['scenes.hotspots', 'media']);

            $copy = $tour->replicate();
            $copy->title = $tour->title.' (کپی)';
            $copy->slug = $this->uniqueSlug($copy->title);
            $copy->status = 'draft';
            $copy->created_by = $user->id;
            $copy->view_count = 0;
            $copy->published_at = null;
            $copy->archived_at = null;
            $copy->share_token = Str::random(32);
            $copy->version = 1;
            $copy->save();

            $sceneMap = [];
            foreach ($tour->scenes as $scene) {
                $newScene = $scene->replicate();
                $newScene->virtual_tour_id = $copy->id;
                $newScene->save();
                $sceneMap[$scene->id] = $newScene->id;
            }

            foreach ($tour->scenes as $scene) {
                $newSceneId = $sceneMap[$scene->id];
                foreach ($scene->hotspots as $hotspot) {
                    $h = $hotspot->replicate();
                    $h->scene_id = $newSceneId;
                    if ($h->target_scene_id && isset($sceneMap[$h->target_scene_id])) {
                        $h->target_scene_id = $sceneMap[$h->target_scene_id];
                    }
                    $h->save();
                }
            }

            $this->logger->log($copy, 'tour.duplicated', $user, null, ['source_id' => $tour->id]);

            return $copy->fresh(['scenes.hotspots', 'media']);
        });
    }

    public function delete(User $user, VirtualTour $tour): void
    {
        $this->logger->log($tour, 'tour.deleted', $user);
        $tour->delete();
    }

    public function updateSharing(User $user, VirtualTour $tour, array $data): VirtualTour
    {
        $updates = [];

        if (isset($data['visibility'])) {
            $updates['visibility'] = $data['visibility'];
        }
        if (array_key_exists('expires_at', $data)) {
            $updates['expires_at'] = $data['expires_at'];
        }
        if (array_key_exists('access_password', $data)) {
            $updates['access_password'] = $data['access_password']
                ? Hash::make($data['access_password'])
                : null;
        }
        if (($data['regenerate_token'] ?? false) || empty($tour->share_token)) {
            $updates['share_token'] = Str::random(32);
        }

        if (isset($data['settings']) && is_array($data['settings'])) {
            $updates['settings'] = array_merge($tour->settings ?? [], $data['settings']);
        }

        $tour->update($updates);
        $this->logger->log($tour, 'tour.sharing_updated', $user);

        return $tour->fresh();
    }

    private function uniqueSlug(string $title): string
    {
        $base = Str::slug($title) ?: 'tour-'.Str::random(6);
        $slug = $base;
        $i = 1;
        while (VirtualTour::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }
}
