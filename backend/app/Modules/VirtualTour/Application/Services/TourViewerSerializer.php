<?php

namespace App\Modules\VirtualTour\Application\Services;

use App\Models\VirtualTour;
use App\Modules\VirtualTour\Application\Contracts\PanoramaStorageInterface;

class TourViewerSerializer
{
    public function __construct(
        private readonly PanoramaStorageInterface $storage,
        private readonly HotspotSerializer $hotspotSerializer,
    ) {}

    public function serialize(VirtualTour $tour): array
    {
        $settings = $tour->settings ?? [];

        return [
            'id' => $tour->id,
            'title' => $tour->title,
            'slug' => $tour->slug,
            'description' => $tour->description,
            'status' => $tour->status,
            'view_count' => $tour->view_count,
            'version' => $tour->version ?? 1,
            'visibility' => $tour->visibility ?? 'public',
            'expires_at' => $tour->expires_at?->toIso8601String(),
            'archived_at' => $tour->archived_at?->toIso8601String(),
            'share_token' => $tour->share_token,
            'has_password' => (bool) $tour->access_password,
            'settings' => $settings,
            'property' => $tour->property ? [
                'code' => $tour->property->code,
                'type' => $tour->property->type?->value ?? $tour->property->type,
                'price' => $tour->property->price,
                'area' => $tour->property->area,
                'city' => $tour->property->city,
                'district' => $tour->property->district,
            ] : null,
            'office' => $tour->office ? [
                'name' => $tour->office->name,
                'phone' => $tour->office->phone ?? ($settings['phone'] ?? null),
            ] : null,
            'scenes' => $tour->scenes->map(fn ($s) => $this->serializeScene($s)),
            'gallery' => $tour->media->map(fn ($m) => [
                'id' => $m->id,
                'type' => $m->type,
                'url' => $this->storage->url($m->path),
                'title' => $m->title,
            ]),
            'public_url' => $tour->publicUrl(),
            'private_url' => $tour->share_token ? $tour->privateUrl() : null,
            'embed_url' => $tour->embedUrl(),
        ];
    }

    public function serializeScene($scene): array
    {
        return [
            'id' => $scene->id,
            'name' => $scene->name,
            'status' => $scene->status ?? 'draft',
            'is_default' => (bool) ($scene->is_default ?? false),
            'is_visible' => (bool) ($scene->is_visible ?? true),
            'panorama_url' => $this->storage->url($scene->panorama_path),
            'thumbnail_url' => $scene->thumbnail_path ? $this->storage->url($scene->thumbnail_path) : null,
            'default_yaw' => (float) $scene->default_yaw,
            'default_pitch' => (float) $scene->default_pitch,
            'default_fov' => $scene->default_fov ? (int) $scene->default_fov : null,
            'background_music' => $scene->background_music,
            'ambient_sound' => $scene->ambient_sound,
            'transition_effect' => $scene->transition_effect ?? 'fade',
            'scene_settings' => $scene->scene_settings ?? [],
            'sort_order' => (int) $scene->sort_order,
            'panorama_width' => $scene->panorama_width,
            'panorama_height' => $scene->panorama_height,
            'file_size' => $scene->file_size,
            'floor_plan_x' => $scene->floor_plan_x,
            'floor_plan_y' => $scene->floor_plan_y,
            'hotspots' => $scene->relationLoaded('hotspots')
                ? $scene->hotspots->map(fn ($h) => $this->hotspotSerializer->serialize($h))
                : [],
        ];
    }
}
