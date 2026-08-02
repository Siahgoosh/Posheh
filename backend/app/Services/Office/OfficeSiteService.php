<?php

namespace App\Services\Office;

use App\Models\Office;
use App\Models\OfficeSitePost;
use App\Models\OfficeVisitRequest;
use App\Models\Property;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OfficeSiteService
{
    public function requestWebsite(User $user, array $data): Office
    {
        $office = $user->office;
        abort_unless($user->canManageOffice(), 403);

        if (! in_array($office->panel_type, ['premium', 'office'], true)) {
            throw ValidationException::withMessages([
                'subdomain' => ['ساخت وبسایت اختصاصی در پلن حرفه‌ای فعال است.'],
            ]);
        }

        $subdomain = $this->normalizeSubdomain($data['subdomain'] ?? $office->slug);

        if (Office::where('subdomain', $subdomain)->where('id', '!=', $office->id)->exists()) {
            throw ValidationException::withMessages([
                'subdomain' => ['این زیردامنه قبلاً ثبت شده است.'],
            ]);
        }

        $office->update([
            'subdomain' => $subdomain,
            'website_description' => $data['description'] ?? $office->description,
            'website_status' => 'pending',
        ]);

        return $office->fresh();
    }

    public function publishPost(User $user, array $data): OfficeSitePost
    {
        $office = $user->office;
        abort_unless($user->canManageOffice(), 403);
        abort_unless(in_array($office->website_status, ['approved', 'published'], true), 403, 'وبسایت هنوز تأیید نشده است.');

        $slug = Str::slug($data['title'] ?? 'post').'-'.Str::random(4);

        return OfficeSitePost::create([
            'office_id' => $office->id,
            'property_id' => $data['property_id'] ?? null,
            'created_by' => $user->id,
            'title' => $data['title'],
            'slug' => $slug,
            'excerpt' => $data['excerpt'] ?? null,
            'body' => $data['body'] ?? null,
            'is_published' => $data['is_published'] ?? true,
        ]);
    }

    public function publicSite(string $subdomain): array
    {
        $office = Office::with(['plan'])
            ->where('subdomain', $subdomain)
            ->where('website_status', 'published')
            ->where('plan_active', true)
            ->where('is_active', true)
            ->firstOrFail();

        $properties = Property::with('media')
            ->where('office_id', $office->id)
            ->where('status', \App\Enums\PropertyStatus::Active)
            ->where('show_on_website', true)
            ->where('website_approved', true)
            ->latest()
            ->limit(48)
            ->get()
            ->map(fn (Property $p) => $this->mapProperty($p))
            ->all();

        $posts = OfficeSitePost::where('office_id', $office->id)
            ->where('is_published', true)
            ->latest()
            ->limit(12)
            ->get()
            ->map(fn (OfficeSitePost $post) => [
                'id' => $post->id,
                'title' => $post->title,
                'slug' => $post->slug,
                'excerpt' => $post->excerpt,
                'body' => $post->body,
                'views' => $post->views,
                'created_at' => optional($post->created_at)->toIso8601String(),
            ])
            ->all();

        $agents = $office->users()
            ->where('is_active', true)
            ->get()
            ->sortBy(fn (User $u) => match ((string) ($u->role->value ?? $u->role)) {
                'office_manager' => 0,
                'consultant' => 1,
                default => 2,
            })
            ->values()
            ->map(fn (User $u) => [
                'name' => $u->name,
                'role_label' => $this->roleLabel((string) ($u->role->value ?? $u->role)),
                'mobile' => $u->mobile,
                'avatar_url' => $u->avatar_path ? url('storage/'.$u->avatar_path) : null,
            ])
            ->all();

        return [
            'office' => [
                'name' => $office->name,
                'brand_name' => $office->getSetting('brand_name') ?: $office->name,
                'brand_color' => $office->getSetting('brand_color') ?: '#0f766e',
                'subdomain' => $office->subdomain,
                'city' => $office->city,
                'address' => $office->address,
                'phone' => $office->phone,
                'whatsapp' => data_get($office->whatsapp_config, 'phone'),
                'description' => $office->website_description ?? $office->description,
                'is_verified' => $office->is_verified,
                'logo_url' => $office->logo_path ? url('storage/'.$office->logo_path) : null,
                'url' => 'https://'.$office->subdomain.'.posheapp.ir',
                'theme' => $this->themePayload($office),
                'stats' => [
                    'properties' => count($properties),
                    'posts' => count($posts),
                    'agents' => count($agents),
                ],
            ],
            'properties' => $properties,
            'posts' => $posts,
            'agents' => $agents,
        ];
    }

    private function mapProperty(Property $p): array
    {
        $cover = $p->coverImage();

        return [
            'id' => $p->id,
            'code' => $p->code,
            'type' => $p->type?->value,
            'type_label' => $p->type?->label(),
            'category_label' => $p->property_category?->label(),
            'price' => $p->price,
            'deposit' => $p->deposit,
            'rent' => $p->rent,
            'area' => $p->area !== null ? (float) $p->area : null,
            'rooms' => $p->rooms,
            'floor' => $p->floor,
            'has_parking' => $p->has_parking,
            'has_elevator' => $p->has_elevator,
            'has_storage' => $p->has_storage,
            'city' => $p->city,
            'district' => $p->district,
            'neighborhood' => $p->neighborhood,
            'description' => $p->description,
            'cover_url' => $cover ? url('storage/'.$cover->path) : null,
            'created_at' => optional($p->created_at)->toIso8601String(),
        ];
    }

    private function roleLabel(string $role): string
    {
        return match ($role) {
            'super_admin' => 'مدیر ارشد',
            'office_manager' => 'مدیر دفتر',
            'consultant' => 'مشاور املاک',
            default => 'مشاور',
        };
    }

    public function submitVisitRequest(string $subdomain, array $data): OfficeVisitRequest
    {
        $office = Office::where('subdomain', $subdomain)
            ->where('website_status', 'published')
            ->firstOrFail();

        return OfficeVisitRequest::create([
            'office_id' => $office->id,
            'property_id' => $data['property_id'] ?? null,
            'name' => $data['name'],
            'mobile' => $data['mobile'],
            'email' => $data['email'] ?? null,
            'preferred_date' => $data['preferred_date'] ?? null,
            'preferred_time' => $data['preferred_time'] ?? null,
            'message' => $data['message'] ?? null,
            'status' => 'new',
        ]);
    }

    public function adminApproveWebsite(Office $office, string $action): Office
    {
        $status = match ($action) {
            'approve' => 'approved',
            'publish' => 'published',
            'reject' => 'rejected',
            'unpublish' => 'approved',
            default => throw ValidationException::withMessages(['action' => ['عملیات نامعتبر']]),
        };

        $updates = ['website_status' => $status];
        if ($status === 'published') {
            $updates['website_published_at'] = now();
            $updates['show_on_website'] = true;
        }

        $office->update($updates);

        return $office->fresh();
    }

    public function adminTogglePlan(Office $office, bool $active): Office
    {
        $office->update(['plan_active' => $active]);

        if (! $active) {
            $office->update(['is_active' => false]);
        } else {
            $office->update(['is_active' => true]);
        }

        return $office->fresh();
    }

    private function normalizeSubdomain(string $value): string
    {
        $value = Str::slug($value);
        $value = preg_replace('/[^a-z0-9-]/', '', $value) ?? $value;

        return substr($value, 0, 63);
    }

    /** @return array<string, mixed> */
    public function themePayload(Office $office): array
    {
        $settings = $office->settings ?? [];
        $themeId = $settings['theme_id'] ?? 'modern';
        $themeConfig = config("office-themes.{$themeId}", config('office-themes.modern'));

        return [
            'id' => $themeId,
            'label' => $themeConfig['label'] ?? 'مدرن',
            'brand_color' => $settings['brand_color'] ?? $themeConfig['defaults']['brand_color'] ?? '#0f766e',
            'hero_title' => $settings['hero_title'] ?? $office->name,
            'hero_subtitle' => $settings['hero_subtitle'] ?? ($office->website_description ?? $office->description),
            'cta_text' => $settings['cta_text'] ?? 'مشاهده املاک',
            'show_stats' => $settings['show_stats'] ?? true,
            'show_team' => $settings['show_team'] ?? true,
            'hero_style' => $settings['hero_style'] ?? $themeConfig['defaults']['hero_style'] ?? 'gradient',
            'card_style' => $settings['card_style'] ?? $themeConfig['defaults']['card_style'] ?? 'glass',
        ];
    }

    public function updateTheme(User $user, array $data): Office
    {
        abort_unless($user->canManageOffice(), 403);
        $office = $user->office;
        $settings = $office->settings ?? [];
        $allowedThemes = array_keys(config('office-themes', []));

        if (isset($data['theme_id']) && ! in_array($data['theme_id'], $allowedThemes, true)) {
            throw ValidationException::withMessages(['theme_id' => ['تم انتخاب‌شده معتبر نیست.']]);
        }

        foreach (['theme_id', 'brand_color', 'hero_title', 'hero_subtitle', 'cta_text', 'show_stats', 'show_team', 'hero_style', 'card_style'] as $key) {
            if (array_key_exists($key, $data)) {
                $settings[$key] = $data[$key];
            }
        }

        $office->update(['settings' => $settings]);

        return $office->fresh();
    }

    /** @return array<int, array<string, string>> */
    public function availableThemes(): array
    {
        return collect(config('office-themes', []))->map(fn ($t, $id) => [
            'id' => $id,
            'label' => $t['label'],
            'description' => $t['description'],
        ])->values()->all();
    }
}
