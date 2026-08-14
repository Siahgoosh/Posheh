<?php

namespace App\Services\BlogImages;

use App\Models\BlogPost;
use App\Services\ContentOps\PromptSecurityService;
use Illuminate\Support\Str;

class ImageBriefBuilder
{
    public const PROMPT_VERSION = 'IMAGE_BRIEF_V1';

    public function __construct(
        private readonly PromptSecurityService $security,
    ) {}

    /** @return array<string,mixed> */
    public function build(BlogPost $post, string $imageType = 'HERO'): array
    {
        $plain = $this->security->sanitizeUntrusted(
            trim(preg_replace('/\s+/u', ' ', strip_tags((string) $post->content)) ?? '')
        );
        $headings = [];
        if (preg_match_all('/<h2[^>]*>(.*?)<\/h2>/iu', (string) $post->content, $m)) {
            $headings = array_slice(array_map(fn ($h) => trim(strip_tags($h)), $m[1]), 0, 5);
        }

        $subject = $post->focus_keyword ?: Str::limit($post->title, 80, '');
        $context = Str::limit($plain, 400, '…');
        $brand = (string) config('blog_images.brand_style');
        $negative = (string) config('blog_images.negative_prompt');

        $composition = match ($imageType) {
            'LOCAL_SCENE' => 'wide establishing scene, atmospheric, no identifiable fake street signs',
            'COMPARISON' => 'clean split composition, abstract comparison without fake charts-as-facts',
            'STEP_BY_STEP' => 'desk workflow documentary scene suggesting process, no readable fake UI KPIs',
            'DIAGRAM', 'INFOGRAPHIC' => 'simple abstract diagram shapes, minimal labels, no Persian text glyphs',
            default => 'hero editorial composition, single clear subject, generous negative space for overlay',
        };

        $prompt = implode("\n", [
            'SUBJECT: '.$subject,
            'CONTEXT: Iranian real-estate SaaS editorial for article «'.$post->title.'»',
            'HEADINGS: '.implode(' | ', $headings),
            'COMPOSITION: '.$composition,
            'CAMERA: 35mm documentary, eye-level',
            'LIGHTING: soft daylight, natural',
            'STYLE: '.$brand,
            'MOOD: trustworthy, calm, professional',
            'BRAND: Posheh — no forged logos',
            'OUTPUT: high-resolution web hero, no text overlays',
            'ARTICLE_SNIPPET: '.$context,
            'NEGATIVE: '.$negative,
            'DISCLOSURE: Illustrative conceptual image — not a real property listing photo',
        ]);

        $slugBase = $post->slug ?: Str::slug(Str::limit($post->title, 60, ''));
        $filename = $slugBase.'-hero.webp';

        return [
            'article_id' => $post->id,
            'purpose' => $imageType === 'HERO' ? 'hero' : strtolower($imageType),
            'subject' => $subject,
            'visual_style' => $brand,
            'composition' => $composition,
            'perspective' => 'eye-level',
            'environment' => 'real-estate office / architecture context',
            'lighting' => 'soft daylight',
            'mood' => 'professional calm',
            'brand_style' => $brand,
            'aspect_ratio' => (string) config('blog_images.default_aspect', '16:9'),
            'resolution' => (string) config('blog_images.default_resolution', '1792x1024'),
            'negative_prompt' => $negative,
            'alt_text' => $this->alt($post, $subject),
            'caption' => null,
            'filename' => $filename,
            'placement' => 'hero',
            'prompt' => $prompt,
            'prompt_version' => self::PROMPT_VERSION,
            'is_illustrative' => true,
            'note' => 'Not a photograph of a specific real property unless sourced from Media Library',
        ];
    }

    private function alt(BlogPost $post, string $subject): string
    {
        $alt = 'تصویر مفهومی مرتبط با '.$subject;
        // Avoid keyword stuffing
        if (mb_strlen($alt) > 120) {
            $alt = Str::limit($alt, 120, '');
        }

        return $alt;
    }
}
