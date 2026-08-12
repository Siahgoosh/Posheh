<?php

namespace App\Services\Seo;

use App\Models\BlogPost;
use App\Models\Seo\SeoLocation;

class LocationPageQualityGate
{
    /**
     * Blocks thin / doorway location pages.
     *
     * @return array{passed: bool, blockers: list<string>, warnings: list<string>, score: int, checks: list<array<string,mixed>>}
     */
    public function evaluate(SeoLocation $location): array
    {
        $blockers = [];
        $warnings = [];
        $checks = [];

        $add = function (string $id, bool $ok, bool $blocking, string $msg) use (&$checks, &$blockers, &$warnings) {
            $checks[] = compact('id', 'ok', 'blocking') + ['message' => $msg];
            if (! $ok && $blocking) {
                $blockers[] = $msg;
            } elseif (! $ok) {
                $warnings[] = $msg;
            }
        };

        $unique = trim((string) $location->unique_value);
        $desc = trim((string) $location->description);
        $add('unique_value', mb_strlen($unique) >= 80, true, 'unique_value الزامی است (≥۸۰ کاراکتر ارزش واقعی محلی)');
        $add('description', mb_strlen($desc) >= 120, true, 'description کافی نیست — صفحه Thin ممنوع');
        $add('name', trim($location->name) !== '', true, 'نام Location الزامی است');
        $add('slug', (bool) preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', (string) $location->slug), true, 'Slug نامعتبر');
        $add('type', in_array($location->type, ['country', 'province', 'city', 'district', 'neighborhood', 'street'], true), true, 'نوع Location نامعتبر');

        if ($location->latitude !== null || $location->longitude !== null) {
            $add('coords_verified', (bool) $location->coords_verified, true, 'مختصات بدون coords_verified=true ممنوع (جعل مختصات ممنوع)');
        } else {
            $add('coords_optional', true, false, 'مختصات اختیاری — جعل نشود');
        }

        $approvedKnowledge = 0;
        if ($location->exists) {
            $approvedKnowledge = $location->knowledge()->where('status', 'approved')->count();
        }
        $add('local_knowledge', $approvedKnowledge >= 1, false, 'حداقل یک Local Knowledge تأییدشده پیشنهاد می‌شود');

        $relatedArticles = 0;
        if ($location->exists && $location->id) {
            $relatedArticles = BlogPost::query()
                ->where('seo_location_id', $location->id)
                ->orWhere(function ($q) use ($location) {
                    $q->where('is_published', true)
                        ->where(function ($qq) use ($location) {
                            $qq->where('title', 'like', '%'.$location->name.'%')
                                ->orWhere('focus_keyword', 'like', '%'.$location->name.'%');
                        });
                })
                ->count();
        }
        $add('related_content', $relatedArticles >= 1, false, 'حداقل یک Article مرتبط پیشنهاد می‌شود');

        // Duplicate thin detection vs siblings
        if ($location->exists && mb_strlen($desc) >= 40) {
            $similar = SeoLocation::query()
                ->where('id', '!=', $location->id)
                ->where('type', $location->type)
                ->whereNotNull('description')
                ->limit(30)
                ->get(['id', 'slug', 'description']);
            foreach ($similar as $other) {
                similar_text(mb_strtolower($desc), mb_strtolower((string) $other->description), $pct);
                if ($pct >= 80) {
                    $add('duplicate_local', false, true, "محتوای ≥۸۰٪ مشابه با /locations/{$other->slug} — cannibalization/thin risk");
                    break;
                }
            }
        }

        $score = (int) max(0, 100 - (count($blockers) * 25) - (count($warnings) * 5));

        return [
            'passed' => $blockers === [],
            'blockers' => $blockers,
            'warnings' => $warnings,
            'score' => $score,
            'checks' => $checks,
            'note' => 'Internal Local Quality Guidance — not a Google Score. Thin doorway pages must not publish.',
        ];
    }
}
