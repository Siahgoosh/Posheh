<?php

namespace App\Services\Seo;

use App\Models\Seo\SeoBusinessProfile;
use App\Models\Seo\SeoEntity;
use App\Models\Seo\SeoEntityRelationship;
use App\Models\Seo\SeoLocation;
use App\Models\Seo\SeoTopic;
use Illuminate\Support\Facades\Schema;

class EntityGraphService
{
    /** @return array<string, mixed> */
    public function snapshot(): array
    {
        if (! Schema::hasTable('seo_entities')) {
            return ['nodes' => [], 'edges' => [], 'note' => 'Migration required'];
        }

        $nodes = SeoEntity::query()->orderBy('type')->orderBy('name')->limit(500)->get([
            'id', 'type', 'name', 'slug', 'status', 'is_indexable',
        ]);
        $edges = SeoEntityRelationship::query()->limit(1000)->get([
            'from_entity_id', 'to_entity_id', 'relation_type', 'weight',
        ]);

        return [
            'nodes' => $nodes,
            'edges' => $edges,
            'counts' => [
                'entities' => SeoEntity::count(),
                'relationships' => SeoEntityRelationship::count(),
                'locations' => Schema::hasTable('seo_locations') ? SeoLocation::count() : 0,
                'topics' => Schema::hasTable('seo_topics') ? SeoTopic::count() : 0,
                'published_locations' => Schema::hasTable('seo_locations') ? SeoLocation::published()->count() : 0,
            ],
            'note' => 'Internal Entity Relationship Graph — not a Google Knowledge Graph claim.',
        ];
    }

    public function relate(int $fromId, int $toId, string $type, int $weight = 50, ?array $meta = null): SeoEntityRelationship
    {
        if (! in_array($type, SeoEntityRelationship::TYPES, true)) {
            throw new \InvalidArgumentException('Invalid relation type');
        }
        if ($fromId === $toId) {
            throw new \InvalidArgumentException('Self-relation not allowed');
        }

        return SeoEntityRelationship::query()->updateOrCreate(
            [
                'from_entity_id' => $fromId,
                'to_entity_id' => $toId,
                'relation_type' => $type,
            ],
            ['weight' => $weight, 'meta' => $meta]
        );
    }

    /** @return list<array<string,mixed>> */
    public function napConsistencyWarnings(): array
    {
        $warnings = [];
        if (! Schema::hasTable('seo_business_profiles')) {
            $warnings[] = [
                'severity' => 'high',
                'code' => 'ENTITY_CONSISTENCY_WARNING',
                'message' => 'seo_business_profiles missing — run migrations + seo:local-bootstrap',
            ];

            return $warnings;
        }
        $profile = SeoBusinessProfile::query()->first();
        if (! $profile) {
            $warnings[] = [
                'severity' => 'high',
                'code' => 'ENTITY_CONSISTENCY_WARNING',
                'message' => 'Business Profile missing — run seo:local-bootstrap',
            ];

            return $warnings;
        }

        $expectedName = trim((string) $profile->business_name);
        $expectedEmail = trim((string) $profile->email);
        $expectedWebsite = rtrim(trim((string) $profile->website), '/');

        // Frontend SITE_CONTACT is mirrored in docs/constants — compare to profile
        if ($expectedEmail && $expectedEmail !== 'info@posheapp.ir') {
            $warnings[] = [
                'severity' => 'medium',
                'code' => 'ENTITY_CONSISTENCY_WARNING',
                'message' => 'Business email differs from default SITE_CONTACT.info — verify frontend constants sync',
                'profile_email' => $expectedEmail,
            ];
        }

        if ($profile->phone && ! $profile->address_line) {
            $warnings[] = [
                'severity' => 'medium',
                'code' => 'NAP_PARTIAL',
                'message' => 'Phone set without address — LocalBusiness schema should stay incomplete until NAP verified',
            ];
        }

        if (($profile->latitude || $profile->longitude) && ! $profile->coords_verified) {
            $warnings[] = [
                'severity' => 'critical',
                'code' => 'COORDS_UNVERIFIED',
                'message' => 'Coordinates present without verification — clear or verify; do not invent geo',
            ];
        }

        if ($expectedName === '') {
            $warnings[] = [
                'severity' => 'critical',
                'code' => 'NAP_NAME_MISSING',
                'message' => 'Business name empty',
            ];
        }

        if (! $profile->nap_complete) {
            $warnings[] = [
                'severity' => 'info',
                'code' => 'NAP_INCOMPLETE',
                'message' => 'NAP incomplete by design until verified phone/address added — Organization schema uses available fields only',
            ];
        }

        unset($expectedWebsite);

        return $warnings;
    }
}
