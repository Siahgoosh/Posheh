<?php

namespace App\Services\VirtualTour;

use App\Models\Office;
use App\Models\User;
use App\Models\VirtualTour;
use App\Models\VirtualTourLead;
use App\Models\VirtualTourScene;
use App\Models\VirtualTourView;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class VirtualTourService
{
    public function list(User $user)
    {
        $query = VirtualTour::query();

        if (! $this->isPlatformScope($user)) {
            $query->where('office_id', $user->office_id);
        }

        return $query
            ->with(['property:id,code,city', 'office:id,name', 'scenes:id,virtual_tour_id,name'])
            ->withCount('views', 'leads')
            ->latest()
            ->paginate(20);
    }

    public function create(User $user, array $data): VirtualTour
    {
        $officeId = $user->office_id ?? $this->platformOfficeId($data['office_id'] ?? null);

        if (! $officeId) {
            throw new \InvalidArgumentException('office_id is required to create a virtual tour.');
        }

        $slug = $this->uniqueSlug($data['title']);

        return VirtualTour::create([
            'office_id' => $officeId,
            'property_id' => $data['property_id'] ?? null,
            'created_by' => $user->id,
            'title' => $data['title'],
            'slug' => $slug,
            'description' => $data['description'] ?? null,
            'status' => 'draft',
            'settings' => $this->defaultSettings($user),
        ]);
    }

    public function update(User $user, int $id, array $data): VirtualTour
    {
        $tour = $this->findForOffice($user, $id);
        $tour->update(array_filter([
            'title' => $data['title'] ?? null,
            'description' => $data['description'] ?? null,
            'property_id' => $data['property_id'] ?? null,
            'settings' => isset($data['settings']) ? array_merge($tour->settings ?? [], $data['settings']) : null,
            'status' => $data['status'] ?? null,
            'published_at' => ($data['status'] ?? null) === 'published' ? now() : $tour->published_at,
        ], fn ($v) => $v !== null));

        return $tour->fresh(['scenes.hotspots', 'media', 'property']);
    }

    public function findForOffice(User $user, int $id): VirtualTour
    {
        $query = VirtualTour::query()
            ->with(['scenes.hotspots.targetScene', 'media', 'property', 'office']);

        if (! $this->isPlatformScope($user)) {
            $query->where('office_id', $user->office_id);
        }

        return $query->findOrFail($id);
    }

    public function findPublic(string $slug): VirtualTour
    {
        return VirtualTour::published()
            ->where('slug', $slug)
            ->with(['scenes.hotspots.targetScene', 'media', 'property', 'office:id,name,phone,logo_path'])
            ->firstOrFail();
    }

    public function recordView(VirtualTour $tour, ?string $ip, ?string $ua, ?string $referrer): void
    {
        VirtualTourView::create([
            'virtual_tour_id' => $tour->id,
            'ip' => $ip,
            'user_agent' => $ua ? Str::limit($ua, 500) : null,
            'referrer' => $referrer ? Str::limit($referrer, 500) : null,
            'viewed_at' => now(),
        ]);
        $tour->increment('view_count');
    }

    public function submitLead(VirtualTour $tour, array $data): VirtualTourLead
    {
        return VirtualTourLead::create([
            'virtual_tour_id' => $tour->id,
            'name' => $data['name'],
            'mobile' => $data['mobile'],
            'message' => $data['message'] ?? null,
        ]);
    }

    public function addScene(User $user, int $tourId, array $data, ?UploadedFile $panorama = null): VirtualTourScene
    {
        $tour = $this->findForOffice($user, $tourId);
        $path = $panorama
            ? $panorama->store("virtual-tours/{$tour->id}/panoramas", 'public')
            : ($data['panorama_path'] ?? 'demo/panorama-1.jpg');

        return $tour->scenes()->create([
            'name' => $data['name'],
            'panorama_path' => $path,
            'default_yaw' => $data['default_yaw'] ?? 0,
            'default_pitch' => $data['default_pitch'] ?? 0,
            'sort_order' => $data['sort_order'] ?? $tour->scenes()->count(),
            'floor_plan_x' => $data['floor_plan_x'] ?? null,
            'floor_plan_y' => $data['floor_plan_y'] ?? null,
        ]);
    }

    public function updateScene(User $user, int $tourId, int $sceneId, array $data): VirtualTourScene
    {
        $tour = $this->findForOffice($user, $tourId);
        $scene = $tour->scenes()->findOrFail($sceneId);
        $scene->update($data);

        return $scene->fresh('hotspots');
    }

    public function deleteScene(User $user, int $tourId, int $sceneId): void
    {
        $tour = $this->findForOffice($user, $tourId);
        $tour->scenes()->findOrFail($sceneId)->delete();
    }

    public function syncHotspots(User $user, int $tourId, int $sceneId, array $hotspots): VirtualTourScene
    {
        $tour = $this->findForOffice($user, $tourId);
        $scene = $tour->scenes()->findOrFail($sceneId);
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

    public function uploadMedia(User $user, int $tourId, UploadedFile $file, string $type, ?string $title = null)
    {
        $tour = $this->findForOffice($user, $tourId);
        $path = $file->store("virtual-tours/{$tour->id}/media", 'public');

        return $tour->media()->create([
            'type' => $type,
            'path' => $path,
            'title' => $title ?? $file->getClientOriginalName(),
            'sort_order' => $tour->media()->count(),
        ]);
    }

    public function toPublicPayload(VirtualTour $tour): array
    {
        $settings = $tour->settings ?? [];
        $baseUrl = rtrim(config('app.url'), '/');

        return [
            'id' => $tour->id,
            'title' => $tour->title,
            'slug' => $tour->slug,
            'description' => $tour->description,
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
            'scenes' => $tour->scenes->map(fn ($s) => [
                'id' => $s->id,
                'name' => $s->name,
                'panorama_url' => $this->assetUrl($s->panorama_path, $baseUrl),
                'thumbnail_url' => $s->thumbnail_path ? $this->assetUrl($s->thumbnail_path, $baseUrl) : null,
                'default_yaw' => (float) $s->default_yaw,
                'default_pitch' => (float) $s->default_pitch,
                'floor_plan_x' => $s->floor_plan_x,
                'floor_plan_y' => $s->floor_plan_y,
                'hotspots' => $s->hotspots->map(fn ($h) => [
                    'id' => $h->id,
                    'type' => $h->type,
                    'target_scene_id' => $h->target_scene_id,
                    'yaw' => (float) $h->yaw,
                    'pitch' => (float) $h->pitch,
                    'title' => $h->title,
                    'content' => $h->content,
                    'link_url' => $h->link_url,
                    'icon' => $h->icon,
                ]),
            ]),
            'gallery' => $tour->media->map(fn ($m) => [
                'id' => $m->id,
                'type' => $m->type,
                'url' => $this->assetUrl($m->path, $baseUrl),
                'title' => $m->title,
            ]),
            'public_url' => $tour->publicUrl(),
        ];
    }

    private function assetUrl(string $path, string $baseUrl): string
    {
        if (str_starts_with($path, 'http')) {
            return $path;
        }
        if (str_starts_with($path, 'demo/')) {
            return '/demo/'.basename($path);
        }

        return "{$baseUrl}/storage/{$path}";
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

    private function defaultSettings(User $user): array
    {
        $office = $user->office;
        if (! $office && $this->isPlatformScope($user)) {
            $office = Office::where('slug', 'demo-office')->first();
        }

        return [
            'brand_color' => '#6366f1',
            'logo_url' => null,
            'phone' => $office?->phone ?? null,
            'whatsapp' => null,
            'telegram' => null,
            'music_url' => null,
            'narration_url' => null,
            'map_lat' => null,
            'map_lng' => null,
            'show_contact_form' => true,
            'show_gallery' => true,
            'show_floor_plan' => true,
            'enable_vr' => true,
            'enable_gyroscope' => true,
        ];
    }

    private function isPlatformScope(User $user): bool
    {
        return $user->isSuperAdmin() || $user->role->isPlatformStaff();
    }

    private function platformOfficeId(?int $requestedOfficeId): ?int
    {
        if ($requestedOfficeId) {
            return $requestedOfficeId;
        }

        return Office::where('slug', 'demo-office')->value('id')
            ?? Office::query()->orderBy('id')->value('id');
    }
}
