<?php

namespace App\Services\Blog;

use Illuminate\Support\Str;

class BlogArticleGenerator
{
    public const CATEGORIES = [
        'software' => ['label' => 'نرم‌افزار و سامانه املاک', 'pillar' => 'best-real-estate-crm-software-iran'],
        'crm' => ['label' => 'CRM و فروش املاک', 'pillar' => 'best-real-estate-crm-software-iran'],
        'filing' => ['label' => 'فایلینگ و ثبت ملک', 'pillar' => 'property-filing-tips-for-agents'],
        'agency' => ['label' => 'مدیریت دفتر و آژانس', 'pillar' => 'digital-transformation-real-estate-agency'],
        'accounting' => ['label' => 'حسابداری و کمیسیون', 'pillar' => 'real-estate-accounting-commission-guide'],
        'contracts' => ['label' => 'قرارداد و حقوقی', 'pillar' => 'mubayaeh-contract-form-125-guide'],
        'marketing' => ['label' => 'بازاریابی املاک', 'pillar' => 'property-qr-code-marketing'],
        'education' => ['label' => 'آموزش مشاور املاک', 'pillar' => 'solo-agent-software-iran'],
        'digital' => ['label' => 'تحول دیجیتال', 'pillar' => 'digital-transformation-real-estate-agency'],
        'ai' => ['label' => 'هوش مصنوعی در املاک', 'pillar' => 'property-customer-matching-system'],
        'mobile' => ['label' => 'اپلیکیشن موبایل', 'pillar' => 'solo-agent-software-iran'],
        'website' => ['label' => 'وبسایت اختصاصی', 'pillar' => 'real-estate-website-subdomain-guide'],
        'bots' => ['label' => 'ربات تلگرام و واتساپ', 'pillar' => 'telegram-whatsapp-bot-real-estate'],
        'reports' => ['label' => 'گزارش و KPI', 'pillar' => 'real-estate-kpi-reports-dashboard'],
        'security' => ['label' => 'امنیت و OTP', 'pillar' => 'cloud-vs-excel-real-estate-management'],
    ];

    private const CITIES = [
        'تهران', 'مشهد', 'اصفهان', 'شیراز', 'تبریز', 'کرج', 'اهواز', 'قم', 'رشت', 'کرمانشاه',
        'یزد', 'اراک', 'زاهدان', 'همدان', 'کرمان', 'ارومیه', 'قزوین', 'ساری', 'بندرعباس', 'گرگان',
    ];

    private const PROPERTY_TYPES = [
        'آپارتمان', 'ویلا', 'زمین', 'مغازه', 'دفتر اداری', 'کلنگی', 'پنت‌هاوس', 'سوئیت', 'انبار', 'سوله',
    ];

    private const COVER_IMAGES = [
        'https://images.unsplash.com/photo-1560518883-ce09059eeffa?w=1200&h=630&fit=crop',
        'https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?w=1200&h=630&fit=crop',
        'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?w=1200&h=630&fit=crop',
        'https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?w=1200&h=630&fit=crop',
        'https://images.unsplash.com/photo-1600566753190-17f0baa2a6c3?w=1200&h=630&fit=crop',
        'https://images.unsplash.com/photo-1600573472592-401b489a3cdc?w=1200&h=630&fit=crop',
        'https://images.unsplash.com/photo-1600047509807-ba8f99d2cdde?w=1200&h=630&fit=crop',
        'https://images.unsplash.com/photo-1613490493576-7fde63acd811?w=1200&h=630&fit=crop',
        'https://images.unsplash.com/photo-1512917774080-9991f1c4c750?w=1200&h=630&fit=crop',
        'https://images.unsplash.com/photo-1582407947302-fd5ed7fb163f?w=1200&h=630&fit=crop',
    ];

    /** @return array<int, array<string, mixed>> */
    public function pillarArticles(): array
    {
        $pillars = [
            ['slug' => 'best-real-estate-crm-software-iran', 'title' => 'بهترین نرم‌افزار CRM املاک در ایران — راهنمای انتخاب ۱۴۰۴', 'category' => 'crm', 'excerpt' => 'چگونه سامانه مدیریت املاک مناسب دفتر خود را انتخاب کنیم؟', 'keywords' => 'نرم افزار املاک, CRM املاک, سامانه ثبت ملک', 'city' => 'تهران', 'type' => 'آپارتمان', 'focus' => 'انتخاب CRM'],
            ['slug' => 'property-filing-tips-for-agents', 'title' => '۱۰ نکته طلایی برای ثبت حرفه‌ای ملک در سامانه', 'category' => 'filing', 'excerpt' => 'ثبت دقیق اطلاعات ملک باعث فروش سریع‌تر می‌شود.', 'keywords' => 'ثبت ملک, فایلینگ املاک, مشاور املاک', 'city' => 'مشهد', 'type' => 'ویلا', 'focus' => 'ثبت فایل'],
            ['slug' => 'cloud-vs-excel-real-estate-management', 'title' => 'چرا دفتر املاک به جای اکسل به سامانه ابری نیاز دارد؟', 'category' => 'software', 'excerpt' => 'مقایسه عملی اکسل با سامانه ابری پوشه.', 'keywords' => 'سامانه ابری املاک, اکسل املاک', 'city' => 'اصفهان', 'type' => 'آپارتمان', 'focus' => 'مهاجرت دیجیتال'],
            ['slug' => 'real-estate-accounting-commission-guide', 'title' => 'حسابداری و کمیسیون دفتر املاک — راهنمای عملی', 'category' => 'accounting', 'excerpt' => 'مدیریت درآمد، هزینه و کمیسیون مشاوران در یک پنل.', 'keywords' => 'حسابداری دفتر املاک, کمیسیون مشاور', 'city' => 'شیراز', 'type' => 'مغازه', 'focus' => 'حسابداری'],
            ['slug' => 'mubayaeh-contract-form-125-guide', 'title' => 'مبایعه‌نامه فرم ۱۲۵ اتحادیه — راهنمای تنظیم دیجیتال', 'category' => 'contracts', 'excerpt' => 'تنظیم قرارداد رسمی با خروجی PDF و Word در پوشه.', 'keywords' => 'مبایعه نامه, قرارداد املاک, فرم 125', 'city' => 'تهران', 'type' => 'آپارتمان', 'focus' => 'قرارداد'],
            ['slug' => 'property-customer-matching-system', 'title' => 'تطبیق هوشمند ملک و مشتری چگونه فروش را افزایش می‌دهد؟', 'category' => 'ai', 'excerpt' => 'سیستم Match در CRM پوشه و امتیازدهی تطبیق.', 'keywords' => 'تطبیق ملک مشتری, CRM املاک', 'city' => 'تبریز', 'type' => 'آپارتمان', 'focus' => 'تطبیق هوشمند'],
            ['slug' => 'real-estate-website-subdomain-guide', 'title' => 'ساخت وبسایت اختصاصی دفتر املاک با ساب‌دامین', 'category' => 'website', 'excerpt' => 'راه‌اندازی سایت name.posheapp.ir با فایلینگ و درخواست بازدید.', 'keywords' => 'وبسایت املاک, سایت دفتر املاک', 'city' => 'کرج', 'type' => 'ویلا', 'focus' => 'وبسایت'],
            ['slug' => 'telegram-whatsapp-bot-real-estate', 'title' => 'ربات تلگرام و واتساپ برای دفتر املاک', 'category' => 'bots', 'excerpt' => 'اتوماسیون پاسخگویی و ارسال فایل ملک.', 'keywords' => 'ربات تلگرام املاک, واتساپ املاک', 'city' => 'اهواز', 'type' => 'آپارتمان', 'focus' => 'ربات'],
            ['slug' => 'property-qr-code-marketing', 'title' => 'QR کد ملک — بازاریابی آفلاین برای مشاوران', 'category' => 'marketing', 'excerpt' => 'چاپ QR روی بنر و کارت ویزیت برای دسترسی سریع به فایل.', 'keywords' => 'QR کد ملک, بازاریابی املاک', 'city' => 'قم', 'type' => 'زمین', 'focus' => 'QR بازاریابی'],
            ['slug' => 'digital-transformation-real-estate-agency', 'title' => 'تحول دیجیتال دفتر املاک در ۹۰ روز', 'category' => 'digital', 'excerpt' => 'نقشه راه عملی دیجیتال‌سازی از اکسل تا CRM ابری.', 'keywords' => 'تحول دیجیتال املاک, نرم افزار املاک', 'city' => 'رشت', 'type' => 'دفتر اداری', 'focus' => 'تحول دیجیتال'],
            ['slug' => 'solo-agent-software-iran', 'title' => 'بهترین نرم‌افزار برای مشاور املاک مستقل', 'category' => 'software', 'excerpt' => 'پنل تک‌نفره با ۴۸ ساعت رایگان — فایلینگ و CRM پایه.', 'keywords' => 'نرم افزار مشاور مستقل, مشاور املاک', 'city' => 'کرمانشاه', 'type' => 'آپارتمان', 'focus' => 'مشاور مستقل'],
            ['slug' => 'real-estate-kpi-reports-dashboard', 'title' => 'گزارش KPI دفتر املاک — شاخص‌هایی که باید بسنجید', 'category' => 'reports', 'excerpt' => 'درآمد، تبدیل، عملکرد مشاوران و قیف فروش.', 'keywords' => 'گزارش KPI املاک, عملکرد مشاور', 'city' => 'یزد', 'type' => 'آپارتمان', 'focus' => 'KPI'],
        ];

        $articles = [];
        foreach ($pillars as $index => $topic) {
            $cat = self::CATEGORIES[$topic['category']] ?? self::CATEGORIES['software'];
            $related = array_slice(array_column($pillars, 'slug'), 0, 4);
            $related = array_values(array_filter($related, fn ($s) => $s !== $topic['slug']));
            $content = $this->buildContent($topic, $this->pickInternalLinks($topic, $related, $cat['pillar']));
            $wordCount = $this->wordCount($content);

            $articles[] = [
                'slug' => $topic['slug'],
                'title' => $topic['title'],
                'category_slug' => $topic['category'],
                'category_label' => $cat['label'],
                'pillar_slug' => $cat['pillar'],
                'excerpt' => $topic['excerpt'],
                'meta_title' => Str::limit($topic['title'].' | پوشه', 60, ''),
                'meta_description' => Str::limit($topic['excerpt'].' — راهنمای تخصصی پوشه.', 155, ''),
                'keywords' => $topic['keywords'],
                'reading_time' => max(5, (int) ceil($wordCount / 180)),
                'content' => $content,
                'cover_image' => self::COVER_IMAGES[$index % count(self::COVER_IMAGES)],
                'faq' => $this->buildFaq($topic),
                'related_slugs' => array_slice($related, 0, 4),
                'author_name' => 'تیم پوشه',
                'is_published' => true,
                'published_at' => now()->subDays($index + 1),
                'cta_text' => 'شروع ۴۸ ساعت رایگان با پوشه',
                'cta_url' => '/register',
            ];
        }

        return $articles;
    }

    /** @return array<int, array<string, mixed>> */
    public function generate(int $count = 300): array
    {
        $topics = $this->topicDefinitions();
        $byCategory = [];
        foreach ($topics as $t) {
            $byCategory[$t['category']][] = $t['slug'];
        }

        $articles = [];

        foreach (array_slice($topics, 0, $count) as $index => $topic) {
            $cat = self::CATEGORIES[$topic['category']] ?? self::CATEGORIES['software'];
            $slug = $topic['slug'];
            $pool = array_values(array_filter($byCategory[$topic['category']] ?? [], fn ($s) => $s !== $slug));
            $related = array_slice($pool, 0, 4);
            if (count($related) < 2) {
                $related[] = $cat['pillar'];
            }

            $internalLinks = $this->pickInternalLinks($topic, $related, $cat['pillar']);
            $content = $this->buildContent($topic, $internalLinks);
            $wordCount = $this->wordCount($content);
            $readingTime = max(5, (int) ceil($wordCount / 180));

            $articles[] = [
                'slug' => $slug,
                'title' => $topic['title'],
                'category_slug' => $topic['category'],
                'category_label' => $cat['label'],
                'pillar_slug' => $cat['pillar'],
                'excerpt' => $topic['excerpt'],
                'meta_title' => Str::limit($topic['title'].' | پوشه', 60, ''),
                'meta_description' => Str::limit($topic['excerpt'].' — راهنمای تخصصی پوشه برای مشاوران و دفاتر املاک ایران.', 155, ''),
                'keywords' => $topic['keywords'],
                'reading_time' => $readingTime,
                'content' => $content,
                'cover_image' => self::COVER_IMAGES[$index % count(self::COVER_IMAGES)],
                'faq' => $this->buildFaq($topic),
                'related_slugs' => $related,
                'author_name' => 'تیم پوشه',
                'is_published' => true,
                'published_at' => now()->subDays($index + 1)->setHour(10)->setMinute(0),
                'cta_text' => 'شروع ۴۸ ساعت رایگان با پوشه',
                'cta_url' => '/register',
            ];
        }

        return $articles;
    }

    /** @return array<int, array{slug: string, title: string, category: string, excerpt: string, keywords: string, city?: string, type?: string}> */
    private function topicDefinitions(): array
    {
        $topics = [];
        $templates = $this->categoryTemplates();

        foreach (self::CATEGORIES as $catSlug => $cat) {
            $variants = $templates[$catSlug] ?? [];
            foreach ($variants as $i => $variant) {
                $city = self::CITIES[$i % count(self::CITIES)];
                $type = self::PROPERTY_TYPES[$i % count(self::PROPERTY_TYPES)];
                $slug = $catSlug.'-'.$variant['slug'].'-'.($i + 1);

                $title = str_replace(['{city}', '{type}'], [$city, $type], $variant['title']);
                $excerpt = str_replace(['{city}', '{type}'], [$city, $type], $variant['excerpt']);
                $keywords = str_replace(['{city}', '{type}'], [$city, $type], $variant['keywords']);

                $topics[] = [
                    'slug' => $slug,
                    'title' => $title,
                    'category' => $catSlug,
                    'excerpt' => $excerpt,
                    'keywords' => $keywords,
                    'city' => $city,
                    'type' => $type,
                    'focus' => $variant['focus'],
                ];
            }
        }

        return $topics;
    }

    /** 20 templates × 15 categories = 300 */
    private function categoryTemplates(): array
    {
        $base = [
            ['slug' => 'guide', 'title' => 'راهنمای جامع {type} در {city}', 'excerpt' => 'همه نکات عملی مدیریت و فروش {type} در بازار {city}.', 'keywords' => 'املاک {city}, {type}, مشاور املاک', 'focus' => 'راهنمای عملی'],
            ['slug' => 'tips', 'title' => '۱۰ نکته طلایی {type} برای مشاوران {city}', 'excerpt' => 'نکات حرفه‌ای که فروش {type} در {city} را سریع‌تر می‌کند.', 'keywords' => 'نکات املاک, {type}, {city}', 'focus' => 'نکات کاربردی'],
            ['slug' => 'mistakes', 'title' => 'اشتباهات رایج مشاوران {type} در {city}', 'excerpt' => 'از این خطاها در ثبت و بازاریابی {type} دوری کنید.', 'keywords' => 'اشتباهات مشاور املاک, {city}', 'focus' => 'خطاهای رایج'],
            ['slug' => 'pricing', 'title' => 'قیمت‌گذاری {type} در {city} — روش علمی', 'excerpt' => 'چگونه قیمت واقع‌بینانه برای {type} در {city} تعیین کنیم.', 'keywords' => 'قیمت {type}, {city}, کارشناسی قیمت', 'focus' => 'قیمت‌گذاری'],
            ['slug' => 'marketing', 'title' => 'بازاریابی {type} در {city} با ابزار دیجیتال', 'excerpt' => 'استراتژی آنلاین و آفلاین برای جذب خریدار {type}.', 'keywords' => 'بازاریابی املاک, {city}, {type}', 'focus' => 'بازاریابی'],
            ['slug' => 'legal', 'title' => 'نکات حقوقی معامله {type} در {city}', 'excerpt' => 'چک‌لیست قرارداد و مدارک قبل از معامله {type}.', 'keywords' => 'قرارداد املاک, {type}, {city}', 'focus' => 'حقوقی'],
            ['slug' => 'crm-use', 'title' => 'استفاده از CRM برای فروش {type} در {city}', 'excerpt' => 'قیف فروش و پیگیری مشتری برای {type} در بازار {city}.', 'keywords' => 'CRM املاک, {city}, {type}', 'focus' => 'CRM'],
            ['slug' => 'filing', 'title' => 'ثبت حرفه‌ای {type} در سامانه — {city}', 'excerpt' => 'فیلدهای ضروری ثبت {type} برای دیده‌شدن در {city}.', 'keywords' => 'ثبت ملک, فایلینگ, {city}', 'focus' => 'فایلینگ'],
            ['slug' => 'visit', 'title' => 'مدیریت بازدید {type} در {city}', 'excerpt' => 'زمان‌بندی و پیگیری بازدید ملک {type} با تقویم شمسی.', 'keywords' => 'بازدید ملک, {city}, {type}', 'focus' => 'بازدید'],
            ['slug' => 'owner', 'title' => 'ارتباط با مالک {type} در {city}', 'excerpt' => 'جذب فایل انحصاری {type} و حفظ اعتماد مالک.', 'keywords' => 'مالک ملک, {city}, {type}', 'focus' => 'مالک'],
            ['slug' => 'buyer', 'title' => 'مشاوره به خریدار {type} در {city}', 'excerpt' => 'پرسش‌های کلیدی و تطبیق نیاز خریدار با فایل‌های {city}.', 'keywords' => 'خریدار ملک, {city}, {type}', 'focus' => 'خریدار'],
            ['slug' => 'digital', 'title' => 'دیجیتال‌سازی فروش {type} در دفتر {city}', 'excerpt' => 'مهاجرت از روش سنتی به سامانه ابری برای {type}.', 'keywords' => 'تحول دیجیتال, {city}, املاک', 'focus' => 'دیجیتال'],
            ['slug' => 'team', 'title' => 'مدیریت تیم مشاوران {type} در {city}', 'excerpt' => 'تقسیم فایل، KPI و هماهنگی تیم برای {type}.', 'keywords' => 'مدیریت تیم, دفتر املاک, {city}', 'focus' => 'تیم'],
            ['slug' => 'commission', 'title' => 'محاسبه کمیسیون {type} در {city}', 'excerpt' => 'سهم مشاور و دفتر از معامله {type} — روش شفاف.', 'keywords' => 'کمیسیون املاک, {city}, {type}', 'focus' => 'کمیسیون'],
            ['slug' => 'contract', 'title' => 'تنظیم قرارداد {type} در {city}', 'excerpt' => 'بندهای مهم مبایعه‌نامه و اجاره‌نامه {type}.', 'keywords' => 'قرارداد, مبایعه نامه, {city}', 'focus' => 'قرارداد'],
            ['slug' => 'website', 'title' => 'نمایش {type} در وبسایت دفتر {city}', 'excerpt' => 'انتشار فایل {type} در سایت اختصاصی و جذب لید.', 'keywords' => 'وبسایت املاک, {city}, {type}', 'focus' => 'وبسایت'],
            ['slug' => 'telegram', 'title' => 'ارسال {type} در ربات تلگرام — {city}', 'excerpt' => 'اتوماسیون معرفی {type} به مشتریان از طریق ربات.', 'keywords' => 'ربات تلگرام, {city}, املاک', 'focus' => 'تلگرام'],
            ['slug' => 'report', 'title' => 'گزارش عملکرد فروش {type} در {city}', 'excerpt' => 'شاخص‌های KPI برای ارزیابی بازار {type} در {city}.', 'keywords' => 'گزارش KPI, {city}, املاک', 'focus' => 'گزارش'],
            ['slug' => 'security', 'title' => 'امنیت اطلاعات فایل {type} در {city}', 'excerpt' => 'OTP، سطح دسترسی و محافظت از داده‌های {type}.', 'keywords' => 'امنیت املاک, OTP, {city}', 'focus' => 'امنیت'],
            ['slug' => 'ai-match', 'title' => 'تطبیق هوشمند {type} با مشتری در {city}', 'excerpt' => 'استفاده از امتیازدهی و Match برای {type} در {city}.', 'keywords' => 'تطبیق ملک, هوش مصنوعی, {city}', 'focus' => 'تطبیق'],
        ];

        $result = [];
        foreach (array_keys(self::CATEGORIES) as $cat) {
            $result[$cat] = array_map(function ($t) {
                return [
                    'slug' => $t['slug'],
                    'title' => $t['title'],
                    'excerpt' => $t['excerpt'],
                    'keywords' => $t['keywords'],
                    'focus' => $t['focus'],
                ];
            }, $base);
        }

        return $result;
    }

    /** @param array<string, string> $topic @param array<int, string> $links */
    private function buildContent(array $topic, array $links): string
    {
        $city = $topic['city'];
        $type = $topic['type'];
        $focus = $topic['focus'];
        $title = $topic['title'];

        $linkHtml = '';
        foreach ($links as $label => $slug) {
            $linkHtml .= "<li><a href=\"/blog/{$slug}\">{$label}</a></li>\n";
        }

        $html = <<<HTML
<h2>مقدمه: چرا {$focus} در بازار {$type} شهر {$city} اهمیت دارد؟</h2>
<p>بازار املاک {$city} در سال‌های اخیر پرتحول بوده و مشاورانی که از <strong>سامانه مدیریت املاک</strong> حرفه‌ای استفاده می‌کنند، سریع‌تر از رقبا فایل {$type} را ثبت، بازاریابی و به معامله می‌رسانند. در این مقاله به‌صورت عملی و گام‌به‌گام درباره «{$title}» صحبت می‌کنیم. اگر دفتر املاک شما هنوز به اکسل و پیام‌های پراکنده واتساپ متکی است، احتمال از دست رفتن سرنخ و تکرار اطلاعات بسیار بالاست.</p>
<p>پوشه به‌عنوان <a href="/blog/best-real-estate-crm-software-iran">نرم‌افزار CRM املاک</a> ابری، ابزارهای ثبت ملک، قیف فروش، حسابداری و گزارش KPI را در یک پنل یکپارچه ارائه می‌دهد. مطالب این راهنما بر اساس تجربه دفاتر فعال در {$city} و استانداردهای اتحادیه املاک تنظیم شده است.</p>

<h2>وضعیت بازار {$type} در {$city}</h2>
<p>تقاضا برای {$type} در مناطق مختلف {$city} متفاوت است. مشاور حرفه‌ای قبل از قیمت‌گذاری، باید نرخ معاملات اخیر، دسترسی به مترو و مراکز خرید، سن بنا و وضعیت سند را بررسی کند. ثبت دقیق این موارد در سامانه باعث می‌شود جستجوی داخلی دفتر و تطبیق با مشتری در چند ثانیه انجام شود.</p>
<h3>عوامل مؤثر بر قیمت {$type}</h3>
<p>موقعیت جغرافیایی، متراژ مفید، تعداد واحد در طبقه، جهت ساختمان، پارکینگ و انباری از مهم‌ترین عوامل قیمت‌گذاری {$type} در {$city} هستند. توصیه می‌شود برای هر فایل حداقل سه عکس باکیفیت، موقعیت دقیق روی نقشه و توضیحات کامل امکانات ثبت شود. در پوشه می‌توانید سطح دسترسی فایل را «دفتری»، «انحصاری» یا «مشارکتی» تعیین کنید تا امنیت اطلاعات حفظ شود.</p>
<h3>رفتار خریدار و مستأجر در {$city}</h3>
<p>خریداران امروز قبل از بازدید حضوری، در اینترنت جستجو می‌کنند. انتشار {$type} در <a href="/blog/real-estate-website-subdomain-guide">وبسایت اختصاصی دفتر</a> و ارسال لینک از طریق <a href="/blog/telegram-whatsapp-bot-real-estate">ربات تلگرام</a> نرخ پاسخگویی را به‌شدت بالا می‌برد. پیگیری منظم سرنخ در قیف CRM از مرحله «تماس اول» تا «بازدید» و «مذاکره» ضروری است.</p>

<h2>{$focus}: راهکار عملی برای مشاوران و مدیران دفتر</h2>
<p>برای اجرای صحیح {$focus} در حوزه {$type}، ابتدا فرآیند فعلی دفتر را ترسیم کنید: فایل از کجا می‌آید؟ چه کسی ثبت می‌کند؟ بازدید چگونه هماهنگ می‌شود؟ قرارداد چه کسی تنظیم می‌کند؟ سپس هر مرحله را در سامانه دیجیتال تعریف کنید.</p>
<h3>گام اول: ثبت استاندارد فایل {$type}</h3>
<p>از الگوی ثبت یکسان برای همه مشاوران استفاده کنید. کد یکتا، نوع معامله (فروش، رهن، اجاره)، قیمت، متراژ، مالک، شماره تماس و یادداشت بازدید فیلدهای اجباری باشند. آموزش تیم و کنترل کیفیت هفتگی از وظایف مدیر دفتر است. مقاله <a href="/blog/property-filing-tips-for-agents">نکات ثبت حرفه‌ای ملک</a> را بخوانید.</p>
<h3>گام دوم: پیگیری مشتری و تقویم بازدید</h3>
<p>هر تماس ورودی را در CRM به‌عنوان سرنخ ثبت کنید. برای هر سرنخ «تاریخ پیگیری بعدی» تعیین کنید تا هیچ مشتری فراموش نشود. تقویم بازدید شمسی پوشه تداخل زمانی را نشان می‌دهد و یادآور ارسال می‌کند. در {$city} که ترافیک بازدیدها بالاست، زمان‌بندی دقیق تفاوت ایجاد می‌کند.</p>
<h3>گام سوم: بستن معامله و مستندسازی</h3>
<p>پس از توافق، <a href="/blog/mubayaeh-contract-form-125-guide">مبایعه‌نامه فرم ۱۲۵</a> را با خروجی PDF صادر کنید. کمیسیون را در ماژول حسابداری ثبت کنید تا سهم مشاور شفاف محاسبه شود. آرشیو دیجیتال قراردادها برای مراجعات بعدی و حسابرسی ضروری است.</p>

<h2>ابزارهای دیجیتال پیشنهادی پوشه برای {$city}</h2>
<ul>
<li>فایلینگ پیشرفته با جستجوی چندفیلدی و فیلتر منطقه</li>
<li>قیف فروش Kanban از سرنخ تا معامله موفق</li>
<li>تطبیق هوشمند ملک و مشتری با امتیازدهی</li>
<li>حسابداری دفتر و کمیسیون خودکار</li>
<li>گزارش KPI و عملکرد هر مشاور</li>
<li>اپ موبایل اندروید برای کار میدانی</li>
</ul>
<p>برای دفاتری که قصد دارند در {$city} برند مستقل بسازند، فعال‌سازی وبسایت اختصاصی و دامنه .ir گام بعدی است. تحول دیجیتال را در <a href="/blog/digital-transformation-real-estate-agency">نقشه ۹۰ روزه</a> دنبال کنید.</p>

<h2>مطالب مرتبط پیشنهادی</h2>
<ul>
{$linkHtml}
</ul>

<h2>جمع‌بندی</h2>
<p>موفقیت در بازار {$type} شهر {$city} به ترکیب مهارت مذاکره، شبکه مالکان و ابزار مدیریت حرفه‌ای بستگی دارد. با رعایت اصول {$focus}، ثبت استاندارد فایل و استفاده از CRM ابری، هم زمان معامله کاهش می‌یابد و هم رضایت مشتری بالا می‌رود. پوشه را با <strong>۴۸ ساعت رایگان</strong> (پلن مشاور مستقل) یا دوره آزمایشی دفتر امتحان کنید و تفاوت را در هفته اول احساس کنید.</p>
HTML;

        return $html;
    }

    /** @param array<string, string> $topic @param array<int, string> $related @return array<string, string> */
    private function pickInternalLinks(array $topic, array $related, string $pillar): array
    {
        $links = [
            'بهترین CRM املاک ایران' => 'best-real-estate-crm-software-iran',
            'راهنمای ثبت ملک' => 'property-filing-tips-for-agents',
        ];

        foreach (array_slice($related, 0, 3) as $slug) {
            $links['مقاله: '.Str::limit(str_replace('-', ' ', $slug), 40)] = $slug;
        }

        if (! isset($links['ستون: '.Str::limit($pillar, 30)])) {
            $links['مقاله تخصصی مرتبط'] = $pillar;
        }

        return $links;
    }

    /** @param array<string, string> $topic @return array<int, array{question: string, answer: string}> */
    private function buildFaq(array $topic): array
    {
        $city = $topic['city'];
        $type = $topic['type'];

        return [
            [
                'question' => "چگونه {$type} را در {$city} سریع‌تر بفروشیم؟",
                'answer' => "ثبت کامل فایل، قیمت‌گذاری واقع‌بینانه، عکس باکیفیت، انتشار در وبسایت و پیگیری منظم سرنخ در CRM از مؤثرترین روش‌هاست.",
            ],
            [
                'question' => 'آیا پوشه برای مشاور مستقل مناسب است؟',
                'answer' => 'بله، پلن solo با ۴۸ ساعت رایگان شامل فایلینگ، CRM پایه و اپ موبایل است.',
            ],
            [
                'question' => 'تفاوت فایل انحصاری و مشارکتی چیست؟',
                'answer' => 'فایل انحصاری فقط متعلق به یک دفتر است؛ مشارکتی بین چند مشاور به اشتراک گذاشته می‌شود. در پوشه سطح دسترسی قابل تنظیم است.',
            ],
            [
                'question' => "آیا برای دفتر {$city} پشتیبانی دارید؟",
                'answer' => 'پوشه به‌صورت ابری در سراسر ایران در دسترس است و تیم پشتیبانی از طریق تیکت و تلگرام پاسخگوست.',
            ],
        ];
    }

    private function wordCount(string $html): int
    {
        $text = trim(preg_replace('/\s+/u', ' ', strip_tags($html)) ?? '');

        return count(preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY));
    }
}
