<?php

namespace App\Services\Seo;

use App\Models\Seo\SeoBusinessProfile;
use App\Models\Seo\SeoEntity;
use App\Models\Seo\SeoEntityRelationship;
use App\Models\Seo\SeoTopic;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Bootstraps real Posheh business/topic entities. Never invents cities, coords, reviews, or NAP.
 */
class LocalSeoBootstrapService
{
    public function ensureDefaults(): array
    {
        if (! Schema::hasTable('seo_business_profiles')) {
            return ['ok' => false, 'message' => 'Migration required'];
        }

        $profile = SeoBusinessProfile::query()->first();
        if (! $profile) {
            $profile = SeoBusinessProfile::create([
                'business_name' => 'پوشه',
                'brand' => 'پوشه',
                'email' => 'info@posheapp.ir',
                'support_email' => 'support@posheapp.ir',
                'website' => 'https://posheapp.ir',
                'country' => 'IR',
                'description' => 'سامانه ابری ثبت و مدیریت املاک برای مشاوران و دفاتر املاک در ایران.',
                'services' => [
                    'نرم‌افزار مدیریت املاک',
                    'CRM املاک',
                    'سایت اختصاصی دفتر',
                    'تور مجازی',
                ],
                'phone' => null,
                'address_line' => null,
                'city' => null,
                'latitude' => null,
                'longitude' => null,
                'coords_verified' => false,
                'status' => 'active',
            ]);
            $profile->refreshNapComplete();
            $profile->save();
        }

        $business = $this->upsertEntity('BUSINESS', 'پوشه', 'posheh-business', $profile->description, [
            'schema_type' => 'Organization',
            'is_indexable' => false,
            'status' => 'published',
            'payload' => ['profile_id' => $profile->id],
        ]);
        $brand = $this->upsertEntity('BRAND', 'پوشه', 'posheh-brand', 'برند پوشه', [
            'status' => 'published',
            'is_indexable' => false,
        ]);
        $this->relate($business->id, $brand->id, 'OWNS');

        $services = [
            ['نرم‌افزار مدیریت املاک', 'property-management-software', 'PRODUCT'],
            ['CRM املاک', 'real-estate-crm', 'SERVICE'],
            ['سایت دفتر املاک', 'office-website', 'SERVICE'],
            ['تور مجازی املاک', 'virtual-tour', 'SERVICE'],
            ['خرید ملک (راهنما)', 'buying-guide', 'TOPIC'],
            ['فروش ملک (راهنما)', 'selling-guide', 'TOPIC'],
            ['رهن و اجاره (راهنما)', 'renting-guide', 'TOPIC'],
        ];
        foreach ($services as [$name, $slug, $type]) {
            $ent = $this->upsertEntity($type === 'TOPIC' ? 'TOPIC' : $type, $name, $slug, null, [
                'status' => 'published',
                'is_indexable' => false,
            ]);
            $this->relate($business->id, $ent->id, $type === 'PRODUCT' ? 'OFFERS' : 'OFFERS');
        }

        $topicTree = [
            'real-estate' => [
                'name' => 'املاک',
                'children' => [
                    'buying' => 'خرید',
                    'selling' => 'فروش',
                    'renting' => 'رهن و اجاره',
                    'investment' => 'سرمایه‌گذاری',
                    'legal' => 'حقوقی',
                    'finance' => 'مالی',
                    'property-management' => 'مدیریت ملک',
                    'local-market' => 'بازار محلی',
                ],
            ],
        ];

        foreach ($topicTree as $slug => $node) {
            $parent = $this->upsertTopic($node['name'], $slug, null, 80);
            foreach ($node['children'] as $cSlug => $cName) {
                $this->upsertTopic($cName, $cSlug, $parent->id, 70);
            }
        }

        return [
            'ok' => true,
            'business_profile_id' => $profile->id,
            'entities' => SeoEntity::count(),
            'topics' => SeoTopic::count(),
            'locations' => 0,
            'note' => 'No city/neighborhood pages seeded — create only with unique local value + human approval.',
        ];
    }

    /** @param array<string, mixed> $attrs */
    private function upsertEntity(string $type, string $name, string $slug, ?string $description, array $attrs = []): SeoEntity
    {
        return SeoEntity::query()->updateOrCreate(
            ['slug' => $slug],
            array_merge([
                'type' => $type,
                'name' => $name,
                'description' => $description,
                'status' => 'draft',
                'is_indexable' => false,
            ], $attrs)
        );
    }

    private function upsertTopic(string $name, string $slug, ?int $parentId, int $value): SeoTopic
    {
        $entity = $this->upsertEntity('TOPIC', $name, 'topic-'.$slug, null, [
            'status' => 'published',
            'is_indexable' => false,
        ]);

        return SeoTopic::query()->updateOrCreate(
            ['slug' => $slug],
            [
                'name' => $name,
                'parent_id' => $parentId,
                'entity_id' => $entity->id,
                'business_value' => $value,
                'coverage' => 'partial',
                'status' => 'active',
                'search_intent' => $slug === 'local-market' ? 'local' : 'informational',
            ]
        );
    }

    private function relate(int $from, int $to, string $type): void
    {
        if ($from === $to) {
            return;
        }
        SeoEntityRelationship::query()->firstOrCreate(
            [
                'from_entity_id' => $from,
                'to_entity_id' => $to,
                'relation_type' => $type,
            ],
            ['weight' => 50]
        );
    }
}
