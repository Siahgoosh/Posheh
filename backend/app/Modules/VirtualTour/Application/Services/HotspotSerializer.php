<?php

namespace App\Modules\VirtualTour\Application\Services;

use App\Models\VirtualTourHotspot;

class HotspotSerializer
{
    public function serialize(VirtualTourHotspot $hotspot): array
    {
        return [
            'id' => $hotspot->id,
            'type' => $hotspot->type,
            'target_scene_id' => $hotspot->target_scene_id,
            'yaw' => (float) $hotspot->yaw,
            'pitch' => (float) $hotspot->pitch,
            'title' => $hotspot->title,
            'label' => $hotspot->label,
            'tooltip' => $hotspot->tooltip,
            'content' => $hotspot->content,
            'link_url' => $hotspot->link_url,
            'icon' => $hotspot->icon,
            'style' => $hotspot->style ?? [],
            'action' => $hotspot->action ?? [],
            'popup' => $hotspot->popup ?? [],
            'sort_order' => (int) ($hotspot->sort_order ?? 0),
            'target_scene' => $hotspot->relationLoaded('targetScene') && $hotspot->targetScene
                ? ['id' => $hotspot->targetScene->id, 'name' => $hotspot->targetScene->name]
                : null,
        ];
    }
}
