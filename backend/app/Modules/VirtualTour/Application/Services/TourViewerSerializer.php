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

    public function serialize(VirtualTour $tour, bool $public = false): array
    {
        $payload = $this->buildPayload($tour);

        if ($public) {
            unset($payload['share_token'], $payload['private_url']);
            if (($tour->visibility ?? 'public') !== 'private') {
                unset($payload['share_token']);
            }
        }

        return $payload;
    }

    public function serializePublic(VirtualTour $tour): array
    {
        return $this->serialize($tour, true);
    }

    /** @return array<string, mixed> */
    private function buildPayload(VirtualTour $tour): array
    {
        $settings = $tour->settings ?? [];

        return [
            'id' => $tour->id,
            'title' => $tour->title,
            'slug' => $tour->slug,
            'description' => $tour->description,
            'property_id' => $tour->property_id,
            'tour_type' => $tour->tour_type ?? 'panorama_360',
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
        $imageVariants = $scene->image_variants ?? null;
        $resolvedVariants = $imageVariants ? $this->resolveImageVariantUrls($imageVariants) : null;

        return [
            'id' => $scene->id,
            'name' => $scene->name,
            'scene_type' => $scene->scene_type ?? 'equirectangular',
            'status' => $scene->status ?? 'draft',
            'is_default' => (bool) ($scene->is_default ?? false),
            'is_visible' => (bool) ($scene->is_visible ?? true),
            'panorama_url' => $scene->panorama_path ? $this->storage->url($scene->panorama_path) : null,
            'thumbnail_url' => $scene->thumbnail_path ? $this->storage->url($scene->thumbnail_path) : null,
            'image_variants' => $resolvedVariants,
            'metadata' => $scene->metadata ?? [],
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

    /** @param  array<string, mixed>  $variants */
    private function resolveImageVariantUrls(array $variants): array
    {
        $resolved = [];
        foreach (['original', 'thumbnail', 'medium', 'large', 'ultra'] as $key) {
            $path = $variants[$key] ?? null;
            if ($path && is_string($path)) {
                $resolved[$key] = $this->storage->url($path);
            }
        }

        if (isset($variants['width'])) {
            $resolved['width'] = $variants['width'];
        }
        if (isset($variants['height'])) {
            $resolved['height'] = $variants['height'];
        }
        if (isset($variants['format'])) {
            $resolved['format'] = $variants['format'];
        }

        return $resolved;
    }
}
