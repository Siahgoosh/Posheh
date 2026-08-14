<?php

namespace App\Services\ContentOps;

use App\Models\BlogPost;
use App\Models\Content\ContentAiJob;
use App\Models\Content\ContentRepurposeAsset;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class RepurposeEngine
{
    /** @return array<string,mixed> */
    public function run(ContentAiJob $job): array
    {
        $post = BlogPost::findOrFail($job->blog_post_id);
        $plain = trim(preg_replace('/\s+/u', ' ', strip_tags((string) $post->content)) ?? '');
        $excerpt = $post->excerpt ?: Str::limit($plain, 180, '…');
        $title = $post->title;
        $url = '/blog/'.$post->slug;

        $assets = [
            'telegram' => "📌 {$title}\n\n{$excerpt}\n\nادامه: {$url}",
            'instagram' => "{$title}\n\n{$excerpt}\n\n#املاک #پوشه",
            'whatsapp' => "{$title}\n{$excerpt}\n{$url}",
            'newsletter' => "<strong>{$title}</strong><p>{$excerpt}</p><p><a href=\"{$url}\">مطالعه کامل</a></p>",
            'snippet' => $excerpt,
            'faq' => $this->faqSnippet($post),
            'reel' => "ایده ریل: ۳ نکته کلیدی از «{$title}» — بدون آمار جعلی، فقط نکات عملی مقاله.",
            'carousel' => "کاروسل: ۱) مسئله ۲) چک‌لیست ۳) اشتباه رایج ۴) CTA — برگرفته از {$title}",
        ];

        $saved = [];
        foreach ($assets as $channel => $content) {
            $notes = $this->qualityNotes($content, $plain);
            $row = [
                'blog_post_id' => $post->id,
                'job_id' => $job->id,
                'channel' => $channel,
                'content' => $content,
                'status' => 'draft',
                'quality_notes' => $notes,
            ];
            if (Schema::hasTable('content_repurpose_assets')) {
                $saved[] = ContentRepurposeAsset::query()->updateOrCreate(
                    ['blog_post_id' => $post->id, 'channel' => $channel],
                    $row
                );
            } else {
                $saved[] = $row;
            }
        }

        return [
            'text' => count($saved).' repurpose drafts',
            'structured' => [
                'assets' => $saved,
                'policy' => 'Draft only — human approve before publish. No spammy duplicates.',
            ],
            'prompt_tokens' => 6,
            'completion_tokens' => 40,
            'model' => 'repurpose-local',
            'provider' => 'local',
            'confidence' => 0.78,
        ];
    }

    private function faqSnippet(BlogPost $post): string
    {
        $faq = is_array($post->faq) ? $post->faq : [];
        if ($faq === []) {
            return 'FAQ: سوالات واقعی کاربران را از Search Console / CRM اضافه کنید — تولید آمار جعلی ممنوع.';
        }
        $lines = [];
        foreach (array_slice($faq, 0, 3) as $f) {
            $q = $f['question'] ?? '';
            $a = $f['answer'] ?? '';
            if ($q && $a) {
                $lines[] = "س: {$q}\nج: ".Str::limit(strip_tags($a), 160, '…');
            }
        }

        return implode("\n\n", $lines);
    }

    /** @return list<string> */
    private function qualityNotes(string $content, string $sourcePlain): array
    {
        $notes = [];
        similar_text(mb_strtolower($content), mb_strtolower(Str::limit($sourcePlain, 500)), $pct);
        if ($pct >= 95) {
            $notes[] = 'Near-duplicate of source — rewrite for channel voice';
        }
        if (preg_match('/(کلیک کنید|خرید فوری).{0,10}\1/u', $content)) {
            $notes[] = 'Possible spammy CTA repetition';
        }

        return $notes;
    }
}
