<?php

namespace Database\Seeders;

use App\Models\BlogPost;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * ۵۰ مقاله سئو درباره تور مجازی املاک با CTA به پوشه.
 */
class VirtualTourBlogSeeder extends Seeder
{
    private const IMAGES = [
        'https://images.unsplash.com/photo-1560518883-ce09059eeffa?w=1200&q=80',
        'https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?w=1200&q=80',
        'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?w=1200&q=80',
        'https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?w=1200&q=80',
        'https://images.unsplash.com/photo-1600566752355-7c3a5ecc9d50?w=1200&q=80',
        'https://images.unsplash.com/photo-1600047509807-ba8f99d2cdde?w=1200&q=80',
        'https://images.unsplash.com/photo-1613490493576-7fde63acd811?w=1200&q=80',
        'https://images.unsplash.com/photo-1512917774080-9991f1c4c750?w=1200&q=80',
    ];

    public function run(): void
    {
        $topics = $this->topics();
        $now = Carbon::now();

        foreach ($topics as $i => $topic) {
            $slug = $topic['slug'];
            $image = self::IMAGES[$i % count(self::IMAGES)];

            BlogPost::updateOrCreate(
                ['slug' => $slug],
                [
                    'title' => $topic['title'],
                    'excerpt' => $topic['excerpt'],
                    'content' => $this->buildContent($topic, $image),
                    'meta_title' => $topic['meta_title'],
                    'meta_description' => $topic['meta_description'],
                    'keywords' => $topic['keywords'],
                    'category_slug' => 'virtual-tour',
                    'category_label' => 'تور مجازی املاک',
                    'pillar_slug' => 'pillar-virtual-tour',
                    'author_name' => 'تیم پوشه',
                    'reading_time' => $topic['reading_time'] ?? 7,
                    'cover_image' => $image,
                    'faq' => $this->faq($topic),
                    'cta_text' => 'همین الان پوشه رو امتحان کن — رایگان',
                    'cta_url' => '/r/'.$this->landingSlugForKeyword($topic['keyword']),
                    'is_published' => true,
                    'published_at' => $now->copy()->subDays($i),
                    'updated_at' => $now,
                ]
            );
        }

        $this->command?->info('Seeded '.count($topics).' virtual tour blog posts.');
    }

    /** @return list<array<string, mixed>> */
    private function topics(): array
    {
        $keywords = [
            'تور مجازی املاک', 'تور مجازی ملک', 'بازدید مجازی املاک', 'املاک مجازی',
            'بازدید ملک مجازی', 'نرم افزار تور مجازی', 'تور ۳۶۰ املاک', 'واک مجازی ملک',
            'تور مجازی آپارتمان', 'تور مجازی ویلا', 'تور مجازی دفتر املاک', 'فایلینگ املاک',
            'CRM املاک', 'حسابداری املاک', 'نرم افزار حسابداری املاک', 'برنامه CRM املاک',
            'حقوق املاک', 'مبایعه نامه املاک', 'اجاره نامه املاک', 'نرم افزار فایلینگ املاک',
            'مشارکت در ساخت', 'دستیار املاک پوشه', 'نرم افزار املاک پوشه', 'پوشه',
            'بازدید آنلاین ملک', 'پانوراما ۳۶۰ ملک', 'اسمارت واک املاک', 'تور تعاملی ملک',
            'بازاریابی مجازی املاک', 'نمایش ۳۶۰ آپارتمان', 'تور مجازی پروژه ساختمانی',
            'تور مجازی نمایشگاه', 'تور مجازی هتل', 'تور مجازی دفتر کار', 'تور مجازی مغازه',
            'تور مجازی زمین', 'تور مجازی باغ ویلا', 'تور مجازی پنت‌هاوس', 'تور مجازی سوئیت',
            'تور مجازی املاک تجاری', 'تور مجازی املاک اداری', 'تور مجازی املاک مسکونی',
            'تور مجازی املاک لوکس', 'تور مجازی املاک شمال', 'تور مجازی املاک تهران',
            'تور مجازی املاک کیش', 'تور مجازی املاک مشهد', 'تور مجازی املاک اصفهان',
            'تور مجازی املاک شیراز', 'تور مجازی املاک یزد',
        ];

        $angles = [
            'راهنمای کامل', 'مزایا برای آژانس', 'هزینه و بازگشت سرمایه', 'مقایسه با بازدید حضوری',
            'چطور شروع کنیم', 'نکات حرفه‌ای', 'ابزارهای ۱۴۰۴', 'راهنمای مشاوران',
            'برای فروش سریع‌تر', 'افزایش تماس مشتری', 'کاهش بازدید بی‌هدف', 'تجربه خریدار',
            'یکپارچه با فایلینگ', 'اتصال به CRM', 'گزارش و آنالیتیکس', 'اشتراک در شبکه‌ها',
            'امبد در سایت', 'QR کد تور', 'واتساپ و تلگرام', 'دیوار و شبکه‌های اجتماعی',
            'SEO و گوگل', 'موبایل و اندروید', 'کیفیت تصویر', 'تور ۳۶۰ درجه',
            'اسمارت واک', 'انتقال بین صحنه‌ها', 'موزیک و صدا', 'واترمارک و امنیت',
            'رمز و لینک خصوصی', 'پیش‌نمایش قبل از انتشار', 'مدیریت چند صحنه', 'پلان طبقه',
            'مینی‌مپ تور', 'تور خودکار', 'تور راهنما', 'فرم درخواست بازدید',
            'اتصال به ملک در پوشه', 'فایلینگ + تور', 'حسابداری + تور', 'حقوقی و قرارداد',
            'مبایعه‌نامه دیجیتال', 'اجاره‌نامه آنلاین', 'مشارکت در ساخت', 'پروژه‌های ساختمانی',
            'نمونه کار آژانس', 'برندینگ دفتر', 'رقابت در بازار', 'آینده املاک دیجیتال',
            'آموزش گام‌به‌گام', 'خطاهای رایج', 'چک‌لیست انتشار', 'داستان موفقیت',
        ];

        $topics = [];
        for ($i = 0; $i < 50; $i++) {
            $kw = $keywords[$i];
            $angle = $angles[$i];
            $slug = 'virtual-tour-'.($i + 1).'-'.substr(md5($kw.$angle), 0, 8);
            $title = "{$angle} {$kw} | پوشه";
            $topics[] = [
                'slug' => $slug,
                'title' => $title,
                'keyword' => $kw,
                'angle' => $angle,
                'excerpt' => "می‌خوای {$kw} رو درست انجام بدی؟ {$angle} — خودمونی توضیح می‌دیم + لینک صفحه راهکار پوشه.",
                'meta_title' => "{$angle} {$kw} — نرم‌افزار پوشه",
                'meta_description' => "{$angle} {$kw}. ساخت تور ۳۶۰ و اسمارت‌واک، اشتراک‌گذاری، CRM املاک و ۴۸ ساعت رایگان پوشه.",
                'keywords' => "{$kw}, تور مجازی, نرم افزار املاک پوشه, فایلینگ املاک, CRM املاک",
                'reading_time' => 6 + ($i % 4),
            ];
        }

        return $topics;
    }

    private function landingSlugForKeyword(string $kw): string
    {
        $map = [
            'فایلینگ املاک' => 'filing-amlak',
            'CRM املاک' => 'crm-amlak',
            'حسابداری املاک' => 'accounting-amlak',
            'نرم افزار حسابداری املاک' => 'accounting-software-amlak',
            'برنامه CRM املاک' => 'crm-app-amlak',
            'تور مجازی املاک' => 'virtual-tour-amlak',
            'تور مجازی ملک' => 'virtual-tour-property',
            'حقوق املاک' => 'real-estate-law',
            'مبایعه نامه املاک' => 'mubayaeh-amlak',
            'اجاره نامه املاک' => 'ejareh-amlak',
            'نرم افزار فایلینگ املاک' => 'filing-software-amlak',
            'مشارکت در ساخت' => 'construction-partnership',
            'دستیار املاک پوشه' => 'posheh-assistant',
            'نرم افزار املاک پوشه' => 'posheh-software',
            'پوشه' => 'posheh',
            'بازدید مجازی املاک' => 'virtual-visit-amlak',
            'املاک مجازی' => 'virtual-real-estate',
            'بازدید ملک مجازی' => 'virtual-property-visit',
        ];

        return $map[$kw] ?? 'virtual-tour-amlak';
    }

    private function buildContent(array $topic, string $image): string
    {
        $kw = $topic['keyword'];
        $angle = $topic['angle'];
        $landingSlug = $this->landingSlugForKeyword($kw);
        $landingUrl = '/r/'.$landingSlug;

        return <<<HTML
<p>سلام! اگه دنبال <strong>{$kw}</strong> هستی، احتمالاً یا از بازدیدهای بی‌هدف خسته شدی، یا رقیبت لینک تور می‌فرسته و تو هنوز ۱۰ عکس توی واتساپ می‌چسبونی. {$angle} واقعاً کار رو راحت‌تر می‌کنه — بدون حرف‌های سخت و کتابی.</p>

<img src="{$image}" alt="{$kw} با پوشه" loading="lazy" width="1200" height="675" class="rounded-xl w-full my-6 object-cover" />

<h2>چرا الان {$kw} جدیه؟</h2>
<p>مشتری قبل از راه انداختن ماشین، گوگل می‌زنه و دیوار می‌چرخه. اگه فقط عکس داری، نصفش اصلاً تماس نمی‌گیره. تور مجازی + فایلینگ درست = «آها، همینو می‌خوام» — و تو وقتت رو برای بازدید جدی نگه می‌داری.</p>

<h2>{$angle} — توی پوشه چطوره؟</h2>
<p>پوشه همون جاییه که فایل می‌زنی، مشتری رو CRM می‌کنی، قرارداد می‌گیری و <strong>تور مجازی</strong> می‌سازی. ۳۶۰، اسمارت‌واک، فلش بین اتاق‌ها، موزیک تور (روی اندروید هم پخش می‌شه)، لینک عمومی و embed. یک پنل — نه پنج نرم‌افزار قاطی.</p>

<h3>چیزایی که مشاورها واقعاً استفاده می‌کنن</h3>
<ul>
<li>تور ۳۶۰ و واک — با گوشی هم می‌شه شروع کرد</li>
<li>فلش شیک بین صحنه‌ها (مشتری می‌فهمه کجا بره)</li>
<li>یک کلیک واتساپ / QR / سایت دفتر</li>
<li>فرم «می‌خوام بازدید» + ببین کی تور رو دیده</li>
<li>همه‌ش وصله به <a href="/r/filing-amlak">فایلینگ</a> و <a href="/r/crm-amlak">CRM</a></li>
</ul>

<h2>از صفر تا لینک — خلاصه</h2>
<p>۱) ملک رو ثبت کن. ۲) عکس‌ها رو بنداز توی تور. ۳) صحنه‌ها رو به هم وصل کن. ۴) موزیک تور رو انتخاب کن (یکبار برای کل تور). ۵) publish و لینک رو بفرست. همین. جدی.</p>

<p>اگه می‌خوای عمیق‌تر بری، صفحه <a href="{$landingUrl}"><strong>{$kw}</strong></a> رو ببین — همه‌چیز اونجا خودمونی توضیح داده شده.</p>

<h2>برای گوگل و دیوار</h2>
<p>تورهای عمومی توی sitemap می‌رن. یعنی <strong>بازدید مجازی املاک</strong> فقط برای مشتری فعلی نیست — سرنخ جدید هم می‌ده. مقالات وبلاگ پوشه هم (مثل همین) به صفحه راهکار لینک می‌دن تا راحت پیداش کنی.</p>

<div class="my-8 p-6 rounded-2xl border border-primary/30 bg-primary/10 text-center">
<p class="font-semibold mb-2">{$angle} برای {$kw}؟ بزن بریم.</p>
<p class="text-sm mb-4">۴۸ ساعت رایگان — بدون کارت بانکی</p>
<a href="{$landingUrl}" class="inline-block px-6 py-3 rounded-xl bg-primary text-primary-foreground font-medium mr-2">صفحه {$kw}</a>
<a href="/register" class="inline-block px-6 py-3 rounded-xl border border-primary font-medium">ثبت‌نام مستقیم</a>
</div>

<h2>جمع‌بندی خودمونی</h2>
<p>{$kw} سخت نیست — ابزار درست می‌خواد. پوشه از ثبت فایل تا تور و CRM یک خطه. یه بار امتحان کن، شنبه یا هر روز release با خیال راحت برو — تور نمونه بساز، به تیم نشون بده، ببین مشتری چی می‌گه.</p>
HTML;
    }

    /** @return list<array<string, string>> */
    private function faq(array $topic): array
    {
        $kw = $topic['keyword'];

        return [
            [
                'question' => "روی موبایل و اندروید {$kw} خوبه؟",
                'answer' => 'آره. تور پوشه موبایل‌فرنده — موزیک تور هم بین صحنه‌ها قطع نمی‌شه.',
            ],
            [
                'question' => 'رایگان می‌شه تست کرد؟',
                'answer' => '۴۸ ساعت کامل رایگان. بعدش خودت پلن انتخاب می‌کنی — اجبار نیست.',
            ],
            [
                'question' => 'با فایلینگ و CRM یکیه؟',
                'answer' => 'همون پنل. ملک ثبت کردی، همونجا تور بساز و لینک بده.',
            ],
        ];
    }
}
