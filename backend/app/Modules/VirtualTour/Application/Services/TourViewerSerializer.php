<?php

namespace App\Modules\VirtualTour\Application\Services;

use App\Models\VirtualTour;
use App\Modules\VirtualTour\Application\Contracts\PanoramaStorageInterface;

class TourViewerSerializer
{
    public function __construct(
        private readonly PanoramaStorageInterface $storage,
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
            'sort_order' => (int) $scene->sort_order,
            'panorama_width' => $scene->panorama_width,
            'panorama_height' => $scene->panorama_height,
            'file_size' => $scene->file_size,
            'floor_plan_x' => $scene->floor_plan_x,
            'floor_plan_y' => $scene->floor_plan_y,
            'hotspots' => $scene->relationLoaded('hotspots')
                ? $scene->hotspots->map(fn ($h) => [
                    'id' => $h->id,
                    'type' => $h->type,
                    'target_scene_id' => $h->target_scene_id,
                    'yaw' => (float) $h->yaw,
                    'pitch' => (float) $h->pitch,
                    'title' => $h->title,
                    'content' => $h->content,
                    'link_url' => $h->link_url,
                    'icon' => $h->icon,
                ])
                : [],
        ];
    }
}
