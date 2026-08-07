<?php

namespace App\Modules\VirtualTour\Application\Services;

use App\Models\User;
use App\Models\VirtualTour;
use App\Modules\VirtualTour\Domain\TourType;
use Illuminate\Support\Str;

class TourManager
{
    public function __construct(
        private readonly TourViewerSerializer $serializer,
    ) {}

    public function list(User $user)
    {
        $query = VirtualTour::query();
        TourUserScope::applyOfficeScope($query, $user);

        return $query
            ->with(['property:id,code,city', 'office:id,name', 'scenes:id,virtual_tour_id,name,status,is_visible,is_default,thumbnail_path,sort_order'])
            ->withCount('views', 'leads')
            ->latest()
            ->paginate(20);
    }

    public function create(User $user, array $data): VirtualTour
    {
        $slug = $this->uniqueSlug($data['title']);
        $tourType = TourType::tryFrom($data['tour_type'] ?? 'panorama_360') ?? TourType::Panorama360;
        $officeId = TourUserScope::officeId($user, $data['office_id'] ?? null);

        if (! $officeId) {
            throw new \InvalidArgumentException('office_id is required to create a virtual tour.');
        }

        return VirtualTour::create([
            'office_id' => $officeId,
            'property_id' => $data['property_id'] ?? null,
            'created_by' => $user->id,
            'title' => $data['title'],
            'slug' => $slug,
            'description' => $data['description'] ?? null,
            'tour_type' => $tourType->value,
            'status' => 'draft',
            'visibility' => 'public',
            'share_token' => Str::random(32),
            'settings' => $this->defaultSettings($user),
        ]);
    }

    public function update(User $user, int $id, array $data): VirtualTour
    {
        $tour = $this->findForOffice($user, $id);

        $updates = array_filter([
            'title' => $data['title'] ?? null,
            'description' => $data['description'] ?? null,
            'property_id' => $data['property_id'] ?? null,
            'settings' => isset($data['settings']) ? array_merge($tour->settings ?? [], $data['settings']) : null,
            'status' => $data['status'] ?? null,
            'visibility' => $data['visibility'] ?? null,
            'expires_at' => $data['expires_at'] ?? null,
            'published_at' => ($data['status'] ?? null) === 'published' ? now() : $tour->published_at,
        ], fn ($v) => $v !== null);

        $tour->update($updates);

        if (($data['status'] ?? null) === 'published') {
            $tour->scenes()->where('is_visible', true)->update(['status' => 'published']);
            if (! isset($data['visibility'])) {
                $tour->update(['visibility' => 'public']);
            }
        }

        return $tour->fresh(['scenes.hotspots', 'media', 'property']);
    }

    public function findForOffice(User $user, int $id): VirtualTour
    {
        $query = VirtualTour::query()->with(['scenes.hotspots.targetScene', 'media', 'property', 'office']);
        TourUserScope::applyOfficeScope($query, $user);

        return $query->findOrFail($id);
    }

    public function findPublic(string $slug): VirtualTour
    {
        return VirtualTour::published()
            ->where('slug', $slug)
            ->with([
                'scenes' => fn ($q) => $q
                    ->where('is_visible', true)
                    ->where(function ($q) {
                        $q->where('status', 'published')
                            ->orWhereNull('status');
                    })
                    ->orderBy('sort_order'),
                'scenes.hotspots.targetScene',
                'media',
                'property',
                'office:id,name,phone,logo_path',
            ])
            ->firstOrFail();
    }

    public function toPayload(VirtualTour $tour): array
    {
        return $this->serializer->serialize($tour);
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
        if (! $office && TourUserScope::isPlatform($user)) {
            $office = \App\Models\Office::where('slug', 'demo-office')->first();
        }

        return [
            'brand_color' => '#2dd4bf',
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
            'auto_rotate' => false,
            'auto_rotate_speed' => 0.5,
            'auto_tour' => false,
            'auto_tour_interval' => 8,
            'guided_tour' => false,
            'guided_tour_steps' => [],
            'bookmarks' => true,
            'favorites' => true,
            'history' => true,
            'mini_map' => true,
            'floor_selector' => true,
            'embed_enabled' => true,
            'embed_allowed_domains' => [],
            'share_enabled' => true,
            'qr_enabled' => true,
        ];
    }
}
