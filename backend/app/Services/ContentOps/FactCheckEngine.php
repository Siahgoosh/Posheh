<?php

namespace App\Services\ContentOps;

use App\Models\BlogPost;
use App\Models\Content\ContentAiJob;
use App\Models\Content\ContentClaim;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class FactCheckEngine
{
    /** @return array<string,mixed> */
    public function run(ContentAiJob $job): array
    {
        $post = BlogPost::findOrFail($job->blog_post_id);
        $plain = trim(preg_replace('/\s+/u', ' ', strip_tags((string) $post->content)) ?? '');
        $claims = $this->extractClaims($plain);
        $saved = [];

        foreach ($claims as $c) {
            $row = [
                'blog_post_id' => $post->id,
                'job_id' => $job->id,
                'claim' => $c['claim'],
                'source' => null,
                'source_type' => 'unknown',
                'confidence' => $c['confidence'],
                'risk_level' => $c['risk'],
                'status' => $c['requires_human'] ? 'needs_source' : 'pending',
                'requires_human' => $c['requires_human'],
            ];
            if (Schema::hasTable('content_claims')) {
                $saved[] = ContentClaim::create($row);
            } else {
                $saved[] = $row;
            }
        }

        $blocking = collect($claims)->where('requires_human', true)->count();

        return [
            'text' => count($claims).' claims extracted',
            'structured' => [
                'claims' => $claims,
                'requires_human_count' => $blocking,
                'publish_block_if_unresolved' => $blocking > 0,
                'note' => 'Sensitive claims require human approval. No fabricated sources.',
            ],
            'prompt_tokens' => 12,
            'completion_tokens' => 40,
            'model' => 'fact-check-local',
            'provider' => 'local',
            'confidence' => 0.65,
        ];
    }

    /** @return list<array{claim:string,risk:string,confidence:int,requires_human:bool}> */
    public function extractClaims(string $plain): array
    {
        if ($plain === '') {
            return [];
        }
        $sentences = preg_split('/(?<=[.!?؟۔])\s+/u', $plain) ?: [];
        $keywords = config('content_ops.sensitive_claim_keywords', []);
        $out = [];
        foreach ($sentences as $s) {
            $s = trim($s);
            if (mb_strlen($s) < 25) {
                continue;
            }
            $risk = 'low';
            $requires = false;
            foreach ($keywords as $kw) {
                if (mb_stripos($s, (string) $kw) !== false) {
                    $risk = 'high';
                    $requires = true;
                    break;
                }
            }
            if (preg_match('/\d{2,}/u', $s) && preg_match('/(درصد|٪|تومان|میلیون|میلیارد)/u', $s)) {
                $risk = 'critical';
                $requires = true;
            }
            if ($risk === 'low' && count($out) >= 8) {
                continue;
            }
            $out[] = [
                'claim' => Str::limit($s, 400, '…'),
                'risk' => $risk,
                'confidence' => $requires ? 40 : 70,
                'requires_human' => $requires,
            ];
            if (count($out) >= 20) {
                break;
            }
        }

        return $out;
    }

    public function hasBlockingClaims(int $blogPostId): bool
    {
        if (! Schema::hasTable('content_claims')) {
            return false;
        }

        return ContentClaim::query()
            ->where('blog_post_id', $blogPostId)
            ->where('requires_human', true)
            ->whereNotIn('status', ['approved', 'rejected'])
            ->exists();
    }
}
