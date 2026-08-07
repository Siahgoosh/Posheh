<?php

namespace App\Modules\VirtualTour\Application\Services;

use App\Models\User;
use App\Models\VirtualTour;
use App\Models\VirtualTourHotspot;
use App\Models\VirtualTourScene;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

class TourImportExportService
{
    public function __construct(
        private readonly TourViewerSerializer $serializer,
        private readonly TourActivityLogger $logger,
    ) {}

    public function buildExportPayload(VirtualTour $tour): array
    {
        return [
            'format' => 'posheh-virtual-tour',
            'format_version' => 1,
            'exported_at' => now()->toIso8601String(),
            'tour' => $this->serializer->serialize($tour),
        ];
    }

    public function exportJson(VirtualTour $tour): string
    {
        return json_encode($this->buildExportPayload($tour), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    public function exportZip(VirtualTour $tour): string
    {
        $tour->load(['scenes.hotspots', 'media']);
        $tmpDir = storage_path('app/tmp/vt-export-'.Str::uuid());
        mkdir($tmpDir, 0755, true);

        $manifest = $this->buildExportPayload($tour);
        file_put_contents("{$tmpDir}/tour.json", json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        $disk = Storage::disk('public');
        foreach ($tour->scenes as $scene) {
            if (! str_starts_with($scene->panorama_path, 'demo/') && $disk->exists($scene->panorama_path)) {
                $dest = "{$tmpDir}/panoramas/".basename($scene->panorama_path);
                if (! is_dir(dirname($dest))) {
                    mkdir(dirname($dest), 0755, true);
                }
                copy($disk->path($scene->panorama_path), $dest);
            }
        }

        $zipPath = storage_path('app/tmp/tour-'.$tour->id.'-'.time().'.zip');
        $zip = new ZipArchive;
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $this->addDirToZip($zip, $tmpDir, '');
        $zip->close();

        $this->deleteDir($tmpDir);

        return $zipPath;
    }

    public function importJson(User $user, array $payload, ?VirtualTour $existing = null): VirtualTour
    {
        $tourData = $payload['tour'] ?? $payload;

        return DB::transaction(function () use ($user, $tourData, $existing) {
            if ($existing) {
                return $this->importFromSnapshot($user, $existing, ['tour' => $tourData]);
            }

            $tour = VirtualTour::create([
                'office_id' => $user->office_id,
                'created_by' => $user->id,
                'title' => $tourData['title'] ?? 'تور وارد شده',
                'slug' => $this->uniqueSlug($tourData['title'] ?? 'imported-tour'),
                'description' => $tourData['description'] ?? null,
                'status' => 'draft',
                'visibility' => $tourData['visibility'] ?? 'private',
                'settings' => $tourData['settings'] ?? [],
            ]);

            $this->importScenes($tour, $tourData['scenes'] ?? []);
            $this->logger->log($tour, 'tour.imported', $user);

            return $tour->fresh(['scenes.hotspots', 'media']);
        });
    }

    public function importFromSnapshot(User $user, VirtualTour $tour, array $snapshot): VirtualTour
    {
        $tourData = $snapshot['tour'] ?? $snapshot;

        return DB::transaction(function () use ($user, $tour, $tourData) {
            $tour->update([
                'title' => $tourData['title'] ?? $tour->title,
                'description' => $tourData['description'] ?? $tour->description,
                'settings' => $tourData['settings'] ?? $tour->settings,
            ]);

            $tour->scenes()->each(function (VirtualTourScene $scene) {
                $scene->hotspots()->delete();
            });
            $tour->scenes()->delete();

            $this->importScenes($tour, $tourData['scenes'] ?? []);
            $this->logger->log($tour, 'tour.restored', $user);

            return $tour->fresh(['scenes.hotspots', 'media']);
        });
    }

    private function importScenes(VirtualTour $tour, array $scenes): void
    {
        $sceneIdMap = [];
        $pendingHotspots = [];

        foreach ($scenes as $i => $sceneData) {
            $oldSceneId = $sceneData['id'] ?? null;
            $scene = $tour->scenes()->create([
                'name' => $sceneData['name'] ?? "صحنه {$i}",
                'status' => $sceneData['status'] ?? 'draft',
                'is_default' => $sceneData['is_default'] ?? ($i === 0),
                'is_visible' => $sceneData['is_visible'] ?? true,
                'panorama_path' => $this->resolvePanoramaPath($tour->id, $sceneData['panorama_url'] ?? 'demo/sphere.jpg'),
                'thumbnail_path' => null,
                'default_yaw' => $sceneData['default_yaw'] ?? 0,
                'default_pitch' => $sceneData['default_pitch'] ?? 0,
                'default_fov' => $sceneData['default_fov'] ?? null,
                'background_music' => $sceneData['background_music'] ?? null,
                'ambient_sound' => $sceneData['ambient_sound'] ?? null,
                'transition_effect' => $sceneData['transition_effect'] ?? 'fade',
                'scene_settings' => $sceneData['scene_settings'] ?? [],
                'sort_order' => $sceneData['sort_order'] ?? $i,
                'floor_plan_x' => $sceneData['floor_plan_x'] ?? null,
                'floor_plan_y' => $sceneData['floor_plan_y'] ?? null,
            ]);

            if ($oldSceneId) {
                $sceneIdMap[$oldSceneId] = $scene->id;
            }

            foreach ($sceneData['hotspots'] ?? [] as $j => $h) {
                $pendingHotspots[] = [
                    'scene_id' => $scene->id,
                    'target_scene_id' => $h['target_scene_id'] ?? null,
                    'data' => $h,
                    'sort_order' => $h['sort_order'] ?? $j,
                ];
            }
        }

        foreach ($pendingHotspots as $item) {
            $targetId = $item['target_scene_id'];
            if ($targetId && isset($sceneIdMap[$targetId])) {
                $targetId = $sceneIdMap[$targetId];
            } elseif ($targetId) {
                $targetId = null;
            }

            $h = $item['data'];
            VirtualTourHotspot::create([
                'scene_id' => $item['scene_id'],
                'type' => $h['type'] ?? 'info',
                'target_scene_id' => $targetId,
                'yaw' => $h['yaw'] ?? 0,
                'pitch' => $h['pitch'] ?? 0,
                'title' => $h['title'] ?? null,
                'label' => $h['label'] ?? null,
                'tooltip' => $h['tooltip'] ?? null,
                'content' => $h['content'] ?? null,
                'link_url' => $h['link_url'] ?? null,
                'icon' => $h['icon'] ?? 'pin',
                'style' => $h['style'] ?? [],
                'action' => $h['action'] ?? [],
                'popup' => $h['popup'] ?? [],
                'sort_order' => $item['sort_order'],
            ]);
        }
    }

    private function resolvePanoramaPath(int $tourId, string $url): string
    {
        if (str_contains($url, '/storage/')) {
            $path = Str::after($url, '/storage/');
            if (Storage::disk('public')->exists($path)) {
                return $path;
            }
        }
        if (str_starts_with($url, 'demo/')) {
            return $url;
        }

        return 'demo/sphere.jpg';
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

    private function addDirToZip(ZipArchive $zip, string $dir, string $base): void
    {
        foreach (scandir($dir) as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            $path = "{$dir}/{$file}";
            $zipPath = $base ? "{$base}/{$file}" : $file;
            if (is_dir($path)) {
                $this->addDirToZip($zip, $path, $zipPath);
            } else {
                $zip->addFile($path, $zipPath);
            }
        }
    }

    private function deleteDir(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }
        foreach (scandir($dir) as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            $path = "{$dir}/{$file}";
            is_dir($path) ? $this->deleteDir($path) : unlink($path);
        }
        rmdir($dir);
    }
}
