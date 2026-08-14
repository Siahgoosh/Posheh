<?php

namespace App\Services\Cro;

use App\Models\BlogPost;
use App\Models\Cro\CroCta;
use App\Models\Cro\CroCtaRule;
use Illuminate\Support\Facades\Schema;

class CroCtaResolver
{
    /**
     * Resolve soft, non-aggressive CTA for a page/article context.
     *
     * @param  array{category?: ?string, topic?: ?string, intent?: ?string, funnel_stage?: ?string, slug?: ?string, path?: ?string, cta_key?: ?string}  $context
     * @return array<string, mixed>|null
     */
    public function resolve(array $context): ?array
    {
        if (! Schema::hasTable('cro_ctas')) {
            return $this->fallback($context);
        }

        if (! empty($context['cta_key'])) {
            $forced = CroCta::query()->where('key', $context['cta_key'])->where('is_active', true)->first();
            if ($forced) {
                return $this->serialize($forced);
            }
        }

        $rules = CroCtaRule::query()
            ->with('cta')
            ->where('is_active', true)
            ->orderBy('priority')
            ->get();

        foreach ($rules as $rule) {
            if (! $rule->cta || ! $rule->cta->is_active) {
                continue;
            }
            $fieldValue = (string) ($context[$rule->match_field] ?? '');
            if ($fieldValue === '') {
                continue;
            }
            $matched = match ($rule->match_operator) {
                'contains' => str_contains(mb_strtolower($fieldValue), mb_strtolower($rule->match_value)),
                'in' => in_array($fieldValue, array_map('trim', explode(',', $rule->match_value)), true),
                default => mb_strtolower($fieldValue) === mb_strtolower($rule->match_value),
            };
            if ($matched) {
                return $this->serialize($rule->cta);
            }
        }

        // Soft defaults by intent/funnel
        $intent = $context['intent'] ?? null;
        $funnel = $context['funnel_stage'] ?? null;
        $category = $context['category'] ?? null;

        $query = CroCta::query()->where('is_active', true)->orderBy('priority');
        $cta = (clone $query)->when($category, fn ($q) => $q->where('category', $category))->first()
            ?: (clone $query)->when($intent, fn ($q) => $q->where('intent', $intent))->first()
            ?: (clone $query)->when($funnel, fn ($q) => $q->where('funnel_stage', $funnel))->first()
            ?: (clone $query)->where('key', 'default-soft')->first()
            ?: (clone $query)->first();

        return $cta ? $this->serialize($cta) : $this->fallback($context);
    }

    public function resolveForPost(BlogPost $post): array
    {
        $funnel = $post->funnel_stage
            ?: match ($post->search_intent) {
                'transactional' => 'BOFU',
                'commercial' => 'MOFU',
                default => 'TOFU',
            };

        $resolved = $this->resolve([
            'category' => $post->category_slug,
            'topic' => $post->category_slug,
            'intent' => $post->search_intent,
            'funnel_stage' => $funnel,
            'slug' => $post->slug,
            'path' => '/blog/'.$post->slug,
            'cta_key' => $post->cro_cta_key,
        ]);

        // Preserve curated post CTA text/url if set and no stronger rule CTA type product
        if ($post->cta_text || $post->cta_url) {
            $resolved['title'] = $post->cta_text ?: ($resolved['title'] ?? '');
            $resolved['url'] = $post->cta_url ?: ($resolved['url'] ?? '/register');
            if ($post->cta_text) {
                $resolved['button_text'] = $this->buttonFromIntent($post->search_intent, $post->cta_text);
            }
        }

        $resolved['funnel_stage'] = $funnel;

        return $resolved;
    }

    private function buttonFromIntent(?string $intent, string $fallbackTitle): string
    {
        return match ($intent) {
            'commercial' => 'آشنایی با پوشه',
            'transactional' => 'ثبت درخواست',
            default => (mb_strlen($fallbackTitle) <= 28 ? $fallbackTitle : 'مطالب مرتبط را ببین'),
        };
    }

    /** @return array<string, mixed> */
    private function serialize(CroCta $cta): array
    {
        return [
            'id' => $cta->id,
            'key' => $cta->key,
            'title' => $cta->title,
            'description' => $cta->description,
            'button_text' => $cta->button_text,
            'url' => $cta->url,
            'image' => $cta->image,
            'type' => $cta->type,
            'funnel_stage' => $cta->funnel_stage,
            'intent' => $cta->intent,
        ];
    }

    /** @param array<string, mixed> $context @return array<string, mixed> */
    private function fallback(array $context): array
    {
        $intent = $context['intent'] ?? 'informational';
        if ($intent === 'commercial') {
            return [
                'id' => null,
                'key' => 'fallback-product',
                'title' => 'اگر می‌خواهید همین فرآیند را در پنل واقعی ببینید',
                'description' => 'بدون اجبار — فقط آشنایی کوتاه با پوشه',
                'button_text' => 'آشنایی با پوشه',
                'url' => '/register',
                'type' => 'product',
                'funnel_stage' => 'MOFU',
            ];
        }

        return [
            'id' => null,
            'key' => 'fallback-soft',
            'title' => 'مطالب مرتبط را ادامه دهید',
            'description' => 'مسیر یادگیری را بدون فشار تبلیغاتی ادامه دهید',
            'button_text' => 'بازگشت به وبلاگ',
            'url' => '/blog',
            'type' => 'soft',
            'funnel_stage' => 'TOFU',
        ];
    }
}
