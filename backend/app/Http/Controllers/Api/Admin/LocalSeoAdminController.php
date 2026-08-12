<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Seo\SeoBusinessProfile;
use App\Models\Seo\SeoEntity;
use App\Models\Seo\SeoLocalKnowledge;
use App\Models\Seo\SeoLocalOpportunity;
use App\Models\Seo\SeoLocation;
use App\Models\Seo\SeoTopic;
use App\Services\Blog\BlogSitemapService;
use App\Services\Seo\EntityGraphService;
use App\Services\Seo\LocalSeoBootstrapService;
use App\Services\Seo\LocalSeoDashboardService;
use App\Services\Seo\LocalSeoOpportunityService;
use App\Services\Seo\LocationPageQualityGate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class LocalSeoAdminController extends Controller
{
    public function __construct(
        private readonly LocalSeoDashboardService $dashboard,
        private readonly LocalSeoBootstrapService $bootstrap,
        private readonly EntityGraphService $graph,
        private readonly LocationPageQualityGate $gate,
        private readonly LocalSeoOpportunityService $opportunities,
        private readonly BlogSitemapService $sitemap,
    ) {}

    public function dashboard(): JsonResponse
    {
        return response()->json(['data' => $this->dashboard->executive()]);
    }

    public function bootstrap(): JsonResponse
    {
        return response()->json(['data' => $this->bootstrap->ensureDefaults()]);
    }

    public function graph(): JsonResponse
    {
        return response()->json(['data' => $this->graph->snapshot()]);
    }

    public function napScan(): JsonResponse
    {
        return response()->json(['data' => ['warnings' => $this->graph->napConsistencyWarnings()]]);
    }

    public function updateBusiness(Request $request): JsonResponse
    {
        $data = $request->validate([
            'business_name' => ['required', 'string', 'max:120'],
            'legal_name' => ['nullable', 'string', 'max:160'],
            'brand' => ['nullable', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:120'],
            'support_email' => ['nullable', 'email', 'max:120'],
            'website' => ['nullable', 'url', 'max:255'],
            'address_line' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:80'],
            'region' => ['nullable', 'string', 'max:80'],
            'country' => ['nullable', 'string', 'max:80'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'description' => ['nullable', 'string', 'max:2000'],
            'services' => ['nullable', 'array'],
            'social_profiles' => ['nullable', 'array'],
            'logo_url' => ['nullable', 'string', 'max:500'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'coords_verified' => ['sometimes', 'boolean'],
            'working_hours' => ['nullable', 'array'],
        ]);

        if ((isset($data['latitude']) || isset($data['longitude'])) && empty($data['coords_verified'])) {
            return response()->json([
                'message' => 'مختصات بدون coords_verified=true پذیرفته نمی‌شود — جعل مختصات ممنوع.',
            ], 422);
        }

        $profile = SeoBusinessProfile::query()->first() ?? new SeoBusinessProfile;
        $profile->fill($data);
        $profile->refreshNapComplete();
        $profile->save();

        return response()->json(['data' => $profile]);
    }

    public function entities(): JsonResponse
    {
        return response()->json([
            'data' => SeoEntity::query()->orderBy('type')->orderBy('name')->limit(300)->get(),
        ]);
    }

    public function storeEntity(Request $request): JsonResponse
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(SeoEntity::TYPES)],
            'name' => ['required', 'string', 'max:160'],
            'slug' => ['nullable', 'string', 'max:160', 'unique:seo_entities,slug'],
            'description' => ['nullable', 'string'],
            'status' => ['nullable', 'string', 'max:20'],
            'is_indexable' => ['sometimes', 'boolean'],
            'payload' => ['nullable', 'array'],
        ]);
        $data['slug'] = $data['slug'] ?? Str::slug($data['name']) ?: 'entity-'.Str::lower(Str::random(6));
        $data['is_indexable'] = (bool) ($data['is_indexable'] ?? false);
        // Never auto-index thin entities
        if ($data['is_indexable'] && empty($data['description'])) {
            return response()->json(['message' => 'Entity indexable بدون description ممنوع.'], 422);
        }
        $entity = SeoEntity::create($data);

        return response()->json(['data' => $entity], 201);
    }

    public function relate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'from_entity_id' => ['required', 'integer', 'exists:seo_entities,id'],
            'to_entity_id' => ['required', 'integer', 'exists:seo_entities,id', 'different:from_entity_id'],
            'relation_type' => ['required', 'string', 'max:40'],
            'weight' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);
        $rel = $this->graph->relate(
            (int) $data['from_entity_id'],
            (int) $data['to_entity_id'],
            $data['relation_type'],
            (int) ($data['weight'] ?? 50)
        );

        return response()->json(['data' => $rel], 201);
    }

    public function locations(): JsonResponse
    {
        return response()->json([
            'data' => SeoLocation::query()->with('parent:id,name,slug')->orderBy('type')->orderBy('name')->limit(200)->get(),
        ]);
    }

    public function storeLocation(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['nullable', 'string', 'max:160', 'unique:seo_locations,slug'],
            'type' => ['required', Rule::in(['country', 'province', 'city', 'district', 'neighborhood', 'street'])],
            'parent_id' => ['nullable', 'integer', 'exists:seo_locations,id'],
            'description' => ['nullable', 'string'],
            'unique_value' => ['nullable', 'string'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'coords_verified' => ['sometimes', 'boolean'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'portfolio' => ['nullable', Rule::in(['STAR', 'GROW', 'MAINTAIN', 'FIX', 'RETIRE'])],
        ]);
        if ((isset($data['latitude']) || isset($data['longitude'])) && empty($data['coords_verified'])) {
            return response()->json(['message' => 'مختصات تأییدنشده ممنوع.'], 422);
        }
        $data['slug'] = $data['slug'] ?? Str::slug($data['name']) ?: 'loc-'.Str::lower(Str::random(6));
        $data['status'] = 'draft';
        $data['is_indexable'] = false;
        $location = SeoLocation::create($data);
        $gate = $this->gate->evaluate($location);
        $location->update(['quality_gate' => $gate, 'quality_score' => $gate['score']]);

        return response()->json(['data' => $location->fresh(), 'gate' => $gate], 201);
    }

    public function updateLocation(Request $request, int $id): JsonResponse
    {
        $location = SeoLocation::findOrFail($id);
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:120'],
            'description' => ['nullable', 'string'],
            'unique_value' => ['nullable', 'string'],
            'parent_id' => ['nullable', 'integer', 'exists:seo_locations,id'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'coords_verified' => ['sometimes', 'boolean'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'robots_directive' => ['nullable', 'string', 'max:60'],
            'portfolio' => ['nullable', Rule::in(['STAR', 'GROW', 'MAINTAIN', 'FIX', 'RETIRE'])],
            'status' => ['nullable', Rule::in(['draft', 'approved', 'published', 'archived'])],
        ]);
        if ((array_key_exists('latitude', $data) || array_key_exists('longitude', $data)) && empty($data['coords_verified']) && ! $location->coords_verified) {
            if (($data['latitude'] ?? $location->latitude) || ($data['longitude'] ?? $location->longitude)) {
                return response()->json(['message' => 'مختصات تأییدنشده ممنوع.'], 422);
            }
        }
        $location->fill($data);
        if (! empty($data['description']) || ! empty($data['unique_value'])) {
            $location->content_updated_at = now();
        }
        $gate = $this->gate->evaluate($location);
        $location->quality_gate = $gate;
        $location->quality_score = $gate['score'];

        if (($data['status'] ?? null) === 'published') {
            if (! $gate['passed']) {
                return response()->json([
                    'message' => 'Publish مسدود — صفحه Thin/بدون Unique Value ممنوع.',
                    'gate' => $gate,
                ], 422);
            }
            $location->is_indexable = ! str_contains(strtolower((string) $location->robots_directive), 'noindex');
            $location->published_at = $location->published_at ?? now();
        }

        $location->save();
        $this->sitemap->invalidate();

        return response()->json(['data' => $location->fresh(), 'gate' => $gate, 'scorecard' => $this->dashboard->locationScorecard($location->fresh())]);
    }

    public function topics(): JsonResponse
    {
        return response()->json([
            'data' => SeoTopic::query()->with('parent:id,name,slug')->orderBy('name')->get(),
        ]);
    }

    public function storeKnowledge(Request $request): JsonResponse
    {
        $data = $request->validate([
            'location_id' => ['nullable', 'integer', 'exists:seo_locations,id'],
            'topic_id' => ['nullable', 'integer', 'exists:seo_topics,id'],
            'fact_type' => ['nullable', 'string', 'max:40'],
            'fact' => ['required', 'string', 'max:5000'],
            'source' => ['nullable', 'string', 'max:255'],
            'fact_date' => ['nullable', 'date'],
            'confidence' => ['nullable', 'integer', 'min:1', 'max:100'],
            'status' => ['nullable', Rule::in(['draft', 'approved', 'expired'])],
            'author' => ['nullable', 'string', 'max:120'],
            'expires_at' => ['nullable', 'date'],
        ]);
        $sensitive = in_array($data['fact_type'] ?? '', ['market', 'price', 'pricing', 'legal', 'financial'], true);
        if ($sensitive && empty($data['source'])) {
            return response()->json(['message' => 'Market/price/legal/financial بدون Source ممنوع.'], 422);
        }
        // Sensitive local knowledge requires human approval — never auto-approve
        if ($sensitive) {
            $data['status'] = 'draft';
        }
        $row = SeoLocalKnowledge::create($data);

        return response()->json(['data' => $row], 201);
    }

    public function generateOpportunities(): JsonResponse
    {
        $rows = $this->opportunities->generateWeekly();

        return response()->json(['data' => $rows, 'count' => count($rows)]);
    }

    public function scorecard(int $id): JsonResponse
    {
        $location = SeoLocation::findOrFail($id);

        return response()->json(['data' => $this->dashboard->locationScorecard($location)]);
    }
}
