<?php

namespace App\Http\Controllers\Api;

use App\Enums\PropertyStatus;
use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use App\Models\Property;
use App\Models\Seo\SeoBusinessProfile;
use App\Models\Seo\SeoLocation;
use App\Services\Seo\LocationPageQualityGate;
use Illuminate\Http\JsonResponse;

class LocalSeoPublicController extends Controller
{
    public function __construct(
        private readonly LocationPageQualityGate $gate,
    ) {}

    public function business(): JsonResponse
    {
        $profile = SeoBusinessProfile::query()->first();

        return response()->json([
            'data' => $profile ? [
                'business_name' => $profile->business_name,
                'brand' => $profile->brand,
                'email' => $profile->email,
                'support_email' => $profile->support_email,
                'website' => $profile->website,
                'phone' => $profile->phone,
                'address_line' => $profile->address_line,
                'city' => $profile->city,
                'region' => $profile->region,
                'country' => $profile->country,
                'description' => $profile->description,
                'services' => $profile->services,
                'logo_url' => $profile->logo_url,
                'working_hours' => $profile->working_hours,
                // Only expose coords when verified
                'latitude' => $profile->coords_verified ? $profile->latitude : null,
                'longitude' => $profile->coords_verified ? $profile->longitude : null,
                'nap_complete' => $profile->nap_complete,
                'schema_ready_local_business' => filled($profile->phone) && filled($profile->address_line) && $profile->coords_verified,
            ] : null,
            'note' => 'No fabricated NAP. LocalBusiness schema only when verified fields exist.',
        ]);
    }

    public function locations(): JsonResponse
    {
        $items = SeoLocation::published()
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'type', 'parent_id', 'meta_title', 'meta_description', 'published_at']);

        return response()->json(['data' => $items]);
    }

    public function show(string $slug): JsonResponse
    {
        $location = SeoLocation::query()->where('slug', $slug)->firstOrFail();
        if ($location->status !== 'published' || ! $location->is_indexable) {
            abort(404);
        }
        $gate = $this->gate->evaluate($location);
        if (! $gate['passed']) {
            // Safety: never serve thin pages even if mis-flagged
            abort(404);
        }

        $articles = BlogPost::published()
            ->where('seo_location_id', $location->id)
            ->orderByDesc('published_at')
            ->limit(8)
            ->get(['slug', 'title', 'excerpt', 'cover_image', 'published_at']);

        $properties = Property::query()
            ->where('status', PropertyStatus::Active)
            ->where('show_on_website', true)
            ->where(function ($q) use ($location) {
                $q->where('city', $location->name)
                    ->orWhere('neighborhood', $location->name)
                    ->orWhere('district', $location->name);
            })
            ->orderByDesc('updated_at')
            ->limit(12)
            ->get(['id', 'title', 'code', 'city', 'neighborhood', 'district', 'type', 'permission', 'price', 'deposit', 'rent']);

        $knowledge = $location->knowledge()
            ->where('status', 'approved')
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->orderByDesc('fact_date')
            ->limit(20)
            ->get(['fact_type', 'fact', 'source', 'fact_date', 'confidence']);

        $children = $location->children()->published()->get(['name', 'slug', 'type']);
        $parent = $location->parent;

        $robots = $location->robots_directive ?: 'index,follow';

        return response()->json([
            'data' => [
                'id' => $location->id,
                'name' => $location->name,
                'slug' => $location->slug,
                'type' => $location->type,
                'description' => $location->description,
                'unique_value' => $location->unique_value,
                'meta_title' => $location->meta_title,
                'meta_description' => $location->meta_description,
                'robots_directive' => $robots,
                'canonical_path' => '/locations/'.$location->slug,
                'latitude' => $location->coords_verified ? $location->latitude : null,
                'longitude' => $location->coords_verified ? $location->longitude : null,
                'parent' => $parent ? ['name' => $parent->name, 'slug' => $parent->slug, 'type' => $parent->type] : null,
                'children' => $children,
                'articles' => $articles,
                'properties' => $properties->map(fn (Property $p) => [
                    'id' => $p->id,
                    'title' => $p->title,
                    'code' => $p->code,
                    'city' => $p->city,
                    'neighborhood' => $p->neighborhood,
                    'type' => $p->type?->value ?? $p->type,
                    'permission' => $p->permission?->value ?? $p->permission,
                    'price' => $p->price,
                ]),
                'knowledge' => $knowledge,
                'published_at' => $location->published_at?->toIso8601String(),
                'content_updated_at' => $location->content_updated_at?->toIso8601String(),
            ],
            'note' => 'Market/price sections omitted unless approved knowledge with source exists. Only live available properties listed.',
        ]);
    }
}
