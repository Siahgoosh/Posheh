<?php

namespace App\Services\Seo;

use App\Models\BlogPost;
use App\Models\Seo\SeoLocalOpportunity;
use App\Models\Seo\SeoLocation;
use App\Models\Seo\SeoTopic;
use App\Models\Seo\SeoTopicScore;
use Illuminate\Support\Facades\Schema;

class LocalSeoOpportunityService
{
    /**
     * Weekly local opportunities — max 10. No fake metrics.
     *
     * @return list<SeoLocalOpportunity>
     */
    public function generateWeekly(): array
    {
        if (! Schema::hasTable('seo_local_opportunities')) {
            return [];
        }

        SeoLocalOpportunity::query()->where('status', 'open')->delete();

        $candidates = [];

        // 1. Topics missing coverage
        if (Schema::hasTable('seo_topics')) {
            foreach (SeoTopic::query()->where('coverage', 'missing')->orderByDesc('business_value')->limit(5)->get() as $topic) {
                $candidates[] = [
                    'type' => 'topic_coverage',
                    'title' => 'پوشش موضوع ناقص: '.$topic->name,
                    'reason' => 'Topic coverage=missing — ایجاد Pillar یا Supporting Content',
                    'priority' => 'high',
                    'payload' => ['topic_id' => $topic->id, 'slug' => $topic->slug],
                ];
            }
        }

        // 2. Local intent articles without location link
        $localArticles = BlogPost::query()
            ->where('is_published', true)
            ->where(function ($q) {
                $q->where('search_intent', 'local')
                    ->orWhere('content_type', 'local');
            })
            ->whereNull('seo_location_id')
            ->limit(5)
            ->get(['id', 'slug', 'title']);
        foreach ($localArticles as $post) {
            $candidates[] = [
                'type' => 'article_location_link',
                'title' => 'Article محلی بدون Location Entity: '.$post->title,
                'reason' => 'اتصال seo_location_id پس از تأیید انسانی',
                'priority' => 'medium',
                'payload' => ['blog_post_id' => $post->id, 'slug' => $post->slug],
            ];
        }

        // 3. Draft locations with incomplete quality
        foreach (SeoLocation::query()->where('status', 'draft')->limit(5)->get() as $loc) {
            $candidates[] = [
                'type' => 'location_quality',
                'title' => 'Location Draft نیازمند کیفیت: '.$loc->name,
                'reason' => 'قبل از Publish باید Quality Gate پاس شود — Thin ممنوع',
                'priority' => 'high',
                'payload' => ['location_id' => $loc->id, 'slug' => $loc->slug],
            ];
        }

        // 4. Topic scores with gaps from growth engine
        if (Schema::hasTable('seo_topic_scores')) {
            foreach (SeoTopicScore::query()->orderBy('topic_score')->limit(5)->get() as $row) {
                $gaps = $row->gaps ?? [];
                if ($gaps) {
                    $candidates[] = [
                        'type' => 'topic_gap',
                        'title' => 'Content gap: '.$row->topic,
                        'reason' => is_array($gaps) ? json_encode($gaps, JSON_UNESCAPED_UNICODE) : (string) $gaps,
                        'priority' => 'medium',
                        'payload' => ['topic' => $row->topic, 'authority_score_internal' => $row->authority_score],
                    ];
                }
            }
        }

        // 5. Entity consistency
        $candidates[] = [
            'type' => 'entity_consistency',
            'title' => 'اجرای NAP / Entity Consistency Scan',
            'reason' => 'هفتگی اختلاف Brand/Email/Phone/Address را بررسی کنید',
            'priority' => 'medium',
            'payload' => [],
        ];

        // 6. Internal linking reminder
        $candidates[] = [
            'type' => 'internal_linking',
            'title' => 'تقویت لینک داخلی Location ↔ Topic ↔ Article',
            'reason' => 'فقط روابط واقعی — keyword stuffing ممنوع',
            'priority' => 'low',
            'payload' => [],
        ];

        $candidates = array_slice($candidates, 0, 10);
        $created = [];
        foreach ($candidates as $i => $c) {
            $created[] = SeoLocalOpportunity::create([
                'type' => $c['type'],
                'title' => $c['title'],
                'reason' => $c['reason'],
                'priority' => $c['priority'],
                'rank' => $i + 1,
                'payload' => $c['payload'],
                'status' => 'open',
                'suggested_at' => now(),
            ]);
        }

        return $created;
    }
}
