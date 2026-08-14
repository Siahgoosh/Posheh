<?php

namespace App\Services\Seo;

use App\Services\Blog\PersianTextNormalizer;

class SeoQueryIntelligence
{
    public function __construct(
        private readonly PersianTextNormalizer $normalizer,
    ) {}

    public function normalizeQuery(string $query): array
    {
        $raw = trim($query);

        return [
            'query_raw' => $raw,
            'query_normalized' => $this->normalizer->normalize($raw),
        ];
    }

    /** @return array{intent: string, topic: string, entity: string|null, funnel_stage: string, business_value: int} */
    public function classify(string $query): array
    {
        $n = $this->normalizer->normalize($query);

        $intent = 'informational';
        $funnel = 'TOFU';
        $business = 40;

        if ($this->hasAny($n, ['قیمت', 'هزینه', 'خرید نرم افزار', 'ثبت نام', 'دمو', 'اشتراک', 'پلن'])) {
            $intent = 'commercial';
            $funnel = 'MOFU';
            $business = 75;
        }
        if ($this->hasAny($n, ['دانلود', 'نصب', 'ورود', 'لاگین', 'پرداخت'])) {
            $intent = 'transactional';
            $funnel = 'BOFU';
            $business = 90;
        }
        if ($this->hasAny($n, ['چیست', 'راهنما', 'نکته', 'چگونه', 'چطور', 'آموزش'])) {
            $intent = 'informational';
            $funnel = 'TOFU';
            $business = max($business, 45);
        }

        $topic = 'general';
        $entity = null;
        $map = [
            'crm' => ['crm', 'سی ار ام', 'سرنخ', 'قیف فروش'],
            'filing' => ['فایلینگ', 'ثبت ملک', 'فایل ملک'],
            'accounting' => ['حسابداری', 'کمیسیون', 'تسویه'],
            'contracts' => ['مبایعه', 'قرارداد', 'سند', 'فرم 125', 'فرم ۱۲۵'],
            'website' => ['وبسایت', 'سایت دفتر', 'ساب دامنه', 'ساب‌دامین'],
            'marketing' => ['qr', 'کیو آر', 'بازاریابی', 'آگهی'],
            'digital' => ['تحول دیجیتال', 'اکسل', 'ابری', 'سامانه'],
            'matching' => ['تطبیق', 'match', 'نیازسنجی'],
            'tour' => ['تور مجازی', '۳۶۰', '360', 'smart walk'],
            'local' => ['تهران', 'مشهد', 'اصفهان', 'شیراز', 'کرج'],
        ];
        foreach ($map as $topicKey => $needles) {
            if ($this->hasAny($n, $needles)) {
                $topic = $topicKey;
                $entity = $needles[0];
                break;
            }
        }

        if ($topic === 'crm' || $topic === 'accounting' || $topic === 'website') {
            $business = max($business, 70);
        }

        return [
            'intent' => $intent,
            'topic' => $topic,
            'entity' => $entity,
            'funnel_stage' => $funnel,
            'business_value' => $business,
        ];
    }

    public function clusterKey(string $normalizedQuery, string $intent, string $topic): string
    {
        $tokens = preg_split('/\s+/u', $normalizedQuery) ?: [];
        $tokens = array_values(array_filter($tokens, fn ($t) => mb_strlen($t) >= 3));
        sort($tokens);
        $stem = implode('-', array_slice($tokens, 0, 4));

        return $topic.'|'.$intent.'|'.($stem ?: 'misc');
    }

    /** @param list<string> $needles */
    private function hasAny(string $haystack, array $needles): bool
    {
        foreach ($needles as $needle) {
            if ($needle !== '' && str_contains($haystack, $this->normalizer->normalize($needle))) {
                return true;
            }
        }

        return false;
    }
}
