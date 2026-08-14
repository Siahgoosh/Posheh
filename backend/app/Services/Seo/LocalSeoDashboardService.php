<?php

namespace App\Services\Seo;

use App\Models\BlogPost;
use App\Models\Property;
use App\Models\Seo\SeoBusinessProfile;
use App\Models\Seo\SeoLocalKnowledge;
use App\Models\Seo\SeoLocalOpportunity;
use App\Models\Seo\SeoLocation;
use App\Models\Seo\SeoTopic;
use App\Models\Seo\SeoTopicScore;
use App\Enums\PropertyStatus;
use Illuminate\Support\Facades\Schema;

class LocalSeoDashboardService
{
    public function __construct(
        private readonly EntityGraphService $graph,
        private readonly LocationPageQualityGate $gate,
    ) {}

    /** @return array<string, mixed> */
    public function executive(): array
    {
        $profile = Schema::hasTable('seo_business_profiles') ? SeoBusinessProfile::query()->first() : null;
        $locations = Schema::hasTable('seo_locations') ? SeoLocation::count() : 0;
        $publishedLoc = Schema::hasTable('seo_locations') ? SeoLocation::published()->count() : 0;
        $topics = Schema::hasTable('seo_topics') ? SeoTopic::count() : 0;
        $knowledge = Schema::hasTable('seo_local_knowledge') ? SeoLocalKnowledge::where('status', 'approved')->count() : 0;
        $opps = Schema::hasTable('seo_local_opportunities')
            ? SeoLocalOpportunity::where('status', 'open')->orderBy('rank')->limit(10)->get()
            : collect();

        $localArticles = BlogPost::query()
            ->where(function ($q) {
                $q->where('search_intent', 'local')->orWhere('content_type', 'local');
            })
            ->count();

        $activeProperties = class_exists(Property::class)
            ? Property::query()->where('status', PropertyStatus::Active)->where('show_on_website', true)->count()
            : 0;

        $topicCoverage = Schema::hasTable('seo_topics')
            ? SeoTopic::query()->selectRaw('coverage, count(*) as total')->groupBy('coverage')->pluck('total', 'coverage')
            : [];

        $authoritySamples = Schema::hasTable('seo_topic_scores')
            ? SeoTopicScore::query()->orderByDesc('authority_score')->limit(8)->get([
                'topic', 'authority_score', 'coverage_score', 'quality_score', 'has_pillar', 'supporting_count', 'gaps',
            ])
            : collect();

        return [
            'business' => $profile,
            'nap_warnings' => $this->graph->napConsistencyWarnings(),
            'counts' => [
                'locations' => $locations,
                'published_locations' => $publishedLoc,
                'topics' => $topics,
                'approved_knowledge' => $knowledge,
                'local_articles' => $localArticles,
                'active_web_properties' => $activeProperties,
            ],
            'topic_coverage' => $topicCoverage,
            'opportunities' => $opps,
            'topical_authority_internal' => $authoritySamples,
            'graph' => $this->graph->snapshot(),
            'field_metrics' => [
                'local_clicks' => 'UNKNOWN',
                'local_impressions' => 'UNKNOWN',
                'local_leads_by_city' => 'UNKNOWN_UNTIL_QUERY',
                'location_roi' => 'UNKNOWN',
            ],
            'note' => 'Internal Local SEO Scorecard — not a Google Score. No Knowledge Graph claim. No fake reviews/prices/coords.',
        ];
    }

    /** @return array<string, mixed> */
    public function locationScorecard(SeoLocation $location): array
    {
        $gate = $this->gate->evaluate($location);
        $articles = BlogPost::query()->where('seo_location_id', $location->id)->count();
        $props = 0;
        if ($location->name && class_exists(Property::class)) {
            $props = Property::query()
                ->where('status', PropertyStatus::Active)
                ->where('show_on_website', true)
                ->where(function ($q) use ($location) {
                    $q->where('city', $location->name)
                        ->orWhere('neighborhood', $location->name)
                        ->orWhere('district', $location->name);
                })
                ->count();
        }

        return [
            'location' => $location,
            'entity_completeness' => $gate['score'],
            'content_coverage' => $articles > 0 ? 'partial' : 'missing',
            'related_articles' => $articles,
            'related_live_properties' => $props,
            'quality_gate' => $gate,
            'organic_performance' => 'UNKNOWN',
            'leads' => 'UNKNOWN',
            'note' => 'Internal scorecard — not Google Score',
        ];
    }
}
