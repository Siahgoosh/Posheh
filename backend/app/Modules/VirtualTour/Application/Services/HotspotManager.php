<?php

namespace App\Modules\VirtualTour\Application\Services;

use App\Models\User;
use App\Models\VirtualTourHotspot;
use App\Models\VirtualTourScene;

class HotspotManager
{
    public function __construct(
        private readonly TourManager $tourManager,
    ) {}

    public function sync(User $user, int $tourId, int $sceneId, array $hotspots): VirtualTourScene
    {
        $tour = $this->tourManager->findForOffice($user, $tourId);
        $scene = $tour->scenes()->findOrFail($sceneId);
        $scene->hotspots()->delete();

        foreach ($hotspots as $index => $h) {
            $scene->hotspots()->create($this->mapHotspotPayload($h, $index));
        }

        return $scene->fresh('hotspots.targetScene');
    }

    public function create(User $user, int $tourId, int $sceneId, array $data): VirtualTourHotspot
    {
        $scene = $this->findScene($user, $tourId, $sceneId);
        $sortOrder = $data['sort_order'] ?? $scene->hotspots()->max('sort_order') + 1;

        return $scene->hotspots()->create($this->mapHotspotPayload($data, $sortOrder));
    }

    public function update(User $user, int $tourId, int $sceneId, int $hotspotId, array $data): VirtualTourHotspot
    {
        $hotspot = $this->findHotspot($user, $tourId, $sceneId, $hotspotId);
        $hotspot->update($this->mapHotspotPayload($data, $data['sort_order'] ?? $hotspot->sort_order, false));

        return $hotspot->fresh('targetScene');
    }

    public function delete(User $user, int $tourId, int $sceneId, int $hotspotId): void
    {
        $this->findHotspot($user, $tourId, $sceneId, $hotspotId)->delete();
    }

    private function findScene(User $user, int $tourId, int $sceneId): VirtualTourScene
    {
        $tour = $this->tourManager->findForOffice($user, $tourId);

        return $tour->scenes()->findOrFail($sceneId);
    }

    private function findHotspot(User $user, int $tourId, int $sceneId, int $hotspotId): VirtualTourHotspot
    {
        return $this->findScene($user, $tourId, $sceneId)->hotspots()->findOrFail($hotspotId);
    }

    private function mapHotspotPayload(array $h, int $sortOrder, bool $includePosition = true): array
    {
        $payload = [
            'type' => $h['type'] ?? 'info',
            'target_scene_id' => $h['target_scene_id'] ?? null,
            'title' => $h['title'] ?? null,
            'label' => $h['label'] ?? null,
            'tooltip' => $h['tooltip'] ?? null,
            'content' => $h['content'] ?? null,
            'link_url' => $h['link_url'] ?? null,
            'icon' => $h['icon'] ?? 'pin',
            'style' => $h['style'] ?? null,
            'action' => $h['action'] ?? null,
            'popup' => $h['popup'] ?? null,
            'sort_order' => $sortOrder,
        ];

        if ($includePosition) {
            if (isset($h['position_x'], $h['position_y'])) {
                $payload['position_x'] = $h['position_x'];
                $payload['position_y'] = $h['position_y'];
                $payload['yaw'] = $h['yaw'] ?? 0;
                $payload['pitch'] = $h['pitch'] ?? 0;
            } else {
                $payload['yaw'] = $h['yaw'] ?? 0;
                $payload['pitch'] = $h['pitch'] ?? 0;
            }
            if (isset($h['position_z'])) {
                $payload['position_z'] = $h['position_z'];
            }
        }

        return array_filter($payload, fn ($v) => $v !== null);
    }
}
