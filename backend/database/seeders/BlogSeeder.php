<?php

namespace Database\Seeders;

use App\Models\BlogPost;
use Illuminate\Database\Seeder;

class BlogSeeder extends Seeder
{
    public function run(): void
    {
        $posts = [
            $this->post(
                'best-real-estate-crm-software-iran',
                'بهترین نرم‌افزار CRM املاک در ایران — راهنمای انتخاب ۱۴۰۴',
                'crm',
                'pillar-crm',
                'چگونه سامانه مدیریت املاک مناسب دفتر خود را انتخاب کنیم؟',
                'نرم افزار املاک, CRM املاک, سامانه ثبت ملک',
                8,
                $this->crmContent(),
                $this->crmFaq(),
                ['property-filing-tips-for-agents', 'cloud-vs-excel-real-estate-management'],
            ),
            $this->post(
                'property-filing-tips-for-agents',
                '۱۰ نکته طلایی برای ثبت حرفه‌ای ملک در سامانه',
                'filing',
                'pillar-filing',
                'ثبت دقیق اطلاعات ملک باعث فروش سریع‌تر می‌شود.',
                'ثبت ملک, فایلینگ املاک, مشاور املاک',
                6,
                $this->filingContent(),
                $this->filingFaq(),
                ['best-real-estate-crm-software-iran', 'property-qr-code-marketing'],
            ),
            $this->post(
                'cloud-vs-excel-real-estate-management',
                'چرا دفتر املاک به جای اکسل به سامانه ابری نیاز دارد؟',
                'software',
                'pillar-excel',
                'مقایسه عملی اکسل با سامانه ابری پوشه.',
                'سامانه ابری املاک, اکسل املاک',
                7,
                $this->excelContent(),
                [],
                ['best-real-estate-crm-software-iran'],
            ),
            $this->post(
                'real-estate-accounting-commission-guide',
                'حسابداری و کمیسیون دفتر املاک — راهنمای عملی',
                'accounting',
                'pillar-accounting',
                'مدیریت درآمد، هزینه و کمیسیون مشاوران در یک پنل.',
                'حسابداری دفتر املاک, کمیسیون مشاور',
                9,
                $this->accountingContent(),
                $this->accountingFaq(),
                ['best-real-estate-crm-software-iran'],
            ),
            $this->post(
                'mubayaeh-contract-form-125-guide',
                'مبایعه‌نامه فرم ۱۲۵ اتحادیه — راهنمای تنظیم دیجیتال',
                'contracts',
                'pillar-contracts',
                'تنظیم قرارداد رسمی با خروجی PDF و Word در پوشه.',
                'مبایعه نامه, قرارداد املاک, فرم 125',
                10,
                $this->contractContent(),
                $this->contractFaq(),
                [],
            ),
            $this->post(
                'property-customer-matching-system',
                'تطبیق هوشمند ملک و مشتری چگونه فروش را افزایش می‌دهد؟',
                'crm',
                'pillar-matching',
                'سیستم Match در CRM پوشه و امتیازدهی تطبیق.',
                'تطبیق ملک مشتری, CRM املاک',
                7,
                $this->matchingContent(),
                [],
                ['best-real-estate-crm-software-iran'],
            ),
            $this->post(
                'real-estate-website-subdomain-guide',
                'ساخت وبسایت اختصاصی دفتر املاک با ساب‌دامین',
                'website',
                'pillar-website',
                'راه‌اندازی سایت name.posheapp.ir با فایلینگ و درخواست بازدید.',
                'وبسایت املاک, سایت دفتر املاک',
                8,
                $this->websiteContent(),
                [],
                [],
            ),
            $this->post(
                'telegram-whatsapp-bot-real-estate',
                'ربات تلگرام و واتساپ برای دفتر املاک',
                'bots',
                'pillar-bots',
                'اتوماسیون پاسخگویی و ارسال فایل ملک.',
                'ربات تلگرام املاک, واتساپ املاک',
                6,
                $this->botsContent(),
                [],
                [],
            ),
            $this->post(
                'property-qr-code-marketing',
                'QR کد ملک — بازاریابی آفلاین برای مشاوران',
                'filing',
                'pillar-qr',
                'چاپ QR روی بنر و کارت ویزیت برای دسترسی سریع به فایل.',
                'QR کد ملک, بازاریابی املاک',
                5,
                $this->qrContent(),
                [],
                ['property-filing-tips-for-agents'],
            ),
            $this->post(
                'digital-transformation-real-estate-agency',
                'تحول دیجیتال دفتر املاک در ۹۰ روز',
                'digital',
                'pillar-digital',
                'نقشه راه عملی دیجیتال‌سازی از اکسل تا CRM ابری.',
                'تحول دیجیتال املاک, نرم افزار املاک',
                12,
                $this->digitalContent(),
                $this->digitalFaq(),
                ['cloud-vs-excel-real-estate-management', 'best-real-estate-crm-software-iran'],
            ),
            $this->post(
                'solo-agent-software-iran',
                'بهترین نرم‌افزار برای مشاور املاک مستقل',
                'software',
                'pillar-solo',
                'پنل تک‌نفره با ۴۸ ساعت رایگان — فایلینگ و CRM پایه.',
                'نرم افزار مشاور مستقل, مشاور املاک',
                6,
                $this->soloContent(),
                $this->soloFaq(),
                ['best-real-estate-crm-software-iran'],
            ),
            $this->post(
                'real-estate-kpi-reports-dashboard',
                'گزارش KPI دفتر املاک — شاخص‌هایی که باید بسنجید',
                'reports',
                'pillar-reports',
                'درآمد، تبدیل، عملکرد مشاوران و قیف فروش.',
                'گزارش KPI املاک, عملکرد مشاور',
                8,
                $this->kpiContent(),
                [],
                ['real-estate-accounting-commission-guide'],
            ),
            $this->post(
                'real-estate-sales-funnel-guide',
                'قیف فروش ملک — از سرنخ تا قرارداد در CRM املاک',
                'crm',
                'pillar-sales',
                'راهنمای عملی فروش ملک با CRM املاک: پیگیری سرنخ، بازدید و بستن معامله.',
                'فروش ملک, CRM املاک, فایلینگ املاک, قیف فروش املاک',
                8,
                $this->salesContent(),
                $this->salesFaq(),
                ['best-real-estate-crm-software-iran', 'property-customer-matching-system'],
            ),
            $this->post(
                'rental-property-management-crm',
                'مدیریت اجاره ملک با نرم‌افزار — قرارداد تا پیگیری مستأجر',
                'contracts',
                'pillar-rental',
                'ثبت فایل اجاره، اجاره‌نامه دیجیتال و پیگیری مستأجر در یک پنل.',
                'اجاره ملک, فروش ملک, CRM املاک, قرارداد اجاره',
                7,
                $this->rentalContent(),
                $this->rentalFaq(),
                ['mubayaeh-contract-form-125-guide'],
            ),
            $this->post(
                'real-estate-accounting-software-guide',
                'حسابداری املاک چیست؟ — راهنمای نرم‌افزار مالی دفتر',
                'accounting',
                'pillar-accounting',
                'حسابداری املاک: درآمد، هزینه، کمیسیون و گزارش مالی در پوشه.',
                'حسابداری املاک, حسابداری دفتر املاک, CRM املاک, فایلینگ املاک',
                9,
                $this->accountingSoftwareContent(),
                $this->accountingFaq(),
                ['real-estate-accounting-commission-guide'],
            ),
        ];

        foreach ($posts as $i => $post) {
            BlogPost::updateOrCreate(
                ['slug' => $post['slug']],
                [
                    ...$post,
                    'author_name' => 'تیم پوشه',
                    'is_published' => true,
                    'published_at' => now()->subDays($i * 3 + 1),
                    'cta_text' => 'شروع ۴۸ ساعت رایگان با پوشه',
                    'cta_url' => '/register',
                ]
            );
        }
    }

    private function post(
        string $slug,
        string $title,
        string $categorySlug,
        string $pillarSlug,
        string $excerpt,
        string $keywords,
        int $readingTime,
        string $content,
        array $faq = [],
        array $related = [],
    ): array {
        $categories = [
            'software' => 'نرم‌افزار و سامانه املاک',
            'crm' => 'CRM و فروش املاک',
            'filing' => 'فایلینگ و ثبت ملک',
            'accounting' => 'حسابداری و کمیسیون',
            'contracts' => 'قرارداد و حقوقی',
            'website' => 'وبسایت اختصاصی',
            'bots' => 'ربات تلگرام و واتساپ',
            'digital' => 'تحول دیجیتال',
            'reports' => 'گزارش و KPI',
        ];

        return [
            'slug' => $slug,
            'title' => $title,
            'category_slug' => $categorySlug,
            'category_label' => $categories[$categorySlug] ?? $categorySlug,
            'pillar_slug' => $pillarSlug,
            'excerpt' => $excerpt,
            'meta_title' => $title.' | پوشه',
            'meta_description' => $excerpt.' — راهنمای تخصصی پوشه برای مشاوران املاک ایران.',
            'keywords' => $keywords,
            'reading_time' => $readingTime,
            'content' => $content,
            'faq' => $faq,
            'related_slugs' => $related,
        ];
    }

    private function crmContent(): string
    {
        return <<<'HTML'
<h2>چرا CRM املاک اهمیت دارد؟</h2>
<p>دفاتر املاک مدرن به ابزاری فراتر از اکسل نیاز دارند. <strong>پوشه</strong> ثبت ملک، مدیریت مخاطبین، قیف فروش کانبان و حسابداری را در یک پلتفرم ابری یکپارچه می‌کند.</p>
<h2>معیارهای انتخاب نرم‌افزار</h2>
<ul>
<li>ثبت سریع ملک با کد یکتا و جستجوی پیشرفته</li>
<li>قیف فروش از سرنخ تا بستن معامله</li>
<li>تطبیق هوشمند ملک و مشتری</li>
<li>ورود OTP و پشتیبان‌گیری ابری</li>
<li>نسخه وب، اندروید و ویندوز</li>
</ul>
<h2>مقایسه با روش سنتی</h2>
<p>در روش سنتی، اطلاعات پراکنده در واتساپ، دفترچه و اکسل است. CRM متمرکز باعث کاهش از دست رفتن سرنخ و افزایش نرخ تبدیل می‌شود.</p>
<h2>نتیجه‌گیری</h2>
<p>برای <strong>بهترین CRM املاک در ایران</strong>، پوشه را با ۴۸ ساعت رایگان (پنل فردی) امتحان کنید.</p>
HTML;
    }

    private function crmFaq(): array
    {
        return [
            ['question' => 'CRM املاک چیست؟', 'answer' => 'سیستم مدیریت ارتباط با مشتری و قیف فروش اختصاص صنعت املاک است.'],
            ['question' => 'آیا پوشه برای مشاور مستقل مناسب است؟', 'answer' => 'بله، پلن solo با ۴۸ ساعت رایگان برای مشاوران مستقل طراحی شده.'],
        ];
    }

    private function filingContent(): string
    {
        return <<<'HTML'
<h2>کد ملک و نوع معامله</h2>
<p>کد یکتا و نوع دقیق (فروش، اجاره، رهن) را مشخص کنید.</p>
<h2>عکس و موقعیت</h2>
<p>حداقل ۳ تصویر باکیفیت و موقعیت دقیق روی نقشه ثبت کنید.</p>
<h2>سطح دسترسی</h2>
<p>فایل دفتری، انحصاری یا مشارکتی را درست تعیین کنید.</p>
HTML;
    }

    private function filingFaq(): array
    {
        return [
            ['question' => 'چند فیلد برای ثبت ملک لازم است؟', 'answer' => 'پوشه بیش از ۳۰ فیلد تخصصی شامل قیمت، متراژ، امکانات و مالک دارد.'],
        ];
    }

    private function excelContent(): string
    {
        return <<<'HTML'
<h2>محدودیت اکسل</h2>
<p>نسخه‌بندی ندارد، برای تیم چندنفره مناسب نیست و جستجوی لحظه‌ای ندارد.</p>
<h2>مزیت سامانه ابری</h2>
<ul><li>دسترسی همه مشاوران</li><li>جستجوی سریع</li><li>OTP و امنیت</li></ul>
HTML;
    }

    private function accountingContent(): string
    {
        return <<<'HTML'
<h2>حسابداری دفتر</h2>
<p>ثبت درآمد و هزینه با تاریخ شمسی و گزارش ماهانه.</p>
<h2>کمیسیون خودکار</h2>
<p>محاسبه سهم مشاور از معاملات بسته‌شده و تأیید مدیر.</p>
HTML;
    }

    private function accountingFaq(): array
    {
        return [['question' => 'آیا حسابداری در همه پلن‌ها هست؟', 'answer' => 'حسابداری در پلن دفتر املاک و حرفه‌ای فعال است.']];
    }

    private function contractContent(): string
    {
        return <<<'HTML'
<h2>فرم ۱۲۵ اتحادیه</h2>
<p>پوشه قالب رسمی مبایعه‌نامه با فیلدهای استاندارد و خروجی PDF/Word ارائه می‌دهد.</p>
<h2>پر کردن خودکار</h2>
<p>نام دفتر، کد ملک و تاریخ شمسی خودکار پر می‌شود.</p>
HTML;
    }

    private function contractFaq(): array
    {
        return [['question' => 'آیا قرارداد قانونی است؟', 'answer' => 'قالب بر اساس فرم ۱۲۵ اتحادیه است؛ بازبینی حقوقی توصیه می‌شود.']];
    }

    private function matchingContent(): string
    {
        return '<h2>تطبیق هوشمند</h2><p>بر اساس بودجه، منطقه و نوع ملک، بهترین فایل‌ها برای هر مشتری پیشنهاد می‌شود.</p>';
    }

    private function websiteContent(): string
    {
        return '<h2>وبسایت اختصاصی</h2><p>هر دفتر در پلن حرفه‌ای آدرس name.posheapp.ir با فایلینگ و درخواست بازدید دارد.</p>';
    }

    private function botsContent(): string
    {
        return '<h2>ربات‌ها</h2><p>تلگرام در پلن دفتر و واتساپ در پلن حرفه‌ای برای پاسخگویی سریع.</p>';
    }

    private function qrContent(): string
    {
        return '<h2>QR کد</h2><p>هر ملک QR اختصاصی دارد — روی بنر چاپ کنید تا مشتری مستقیم جزئیات را ببیند.</p>';
    }

    private function digitalContent(): string
    {
        return <<<'HTML'
<h2>فاز ۱: دیجیتال‌سازی فایلینگ</h2>
<p>مهاجرت از اکسل به پوشه و آموزش تیم.</p>
<h2>فاز ۲: CRM و بازدید</h2>
<p>قیف فروش و تقویم بازدید شمسی.</p>
<h2>فاز ۳: گزارش و اتوماسیون</h2>
<p>KPI، ربات و وبسایت.</p>
HTML;
    }

    private function digitalFaq(): array
    {
        return [['question' => 'مهاجرت از اکسل سخت است؟', 'answer' => 'خیر — import اکسل و پشتیبانی پوشه مهاجرت را ساده می‌کند.']];
    }

    private function soloContent(): string
    {
        return '<h2>مشاور مستقل</h2><p>پلن solo با ۴۸ ساعت رایگان — فایلینگ، CRM پایه و اپ موبایل.</p>';
    }

    private function soloFaq(): array
    {
        return [['question' => '۴۸ ساعت رایگان چگونه فعال می‌شود؟', 'answer' => 'با ثبت‌نام پلن مشاور مستقل، بدون کارت بانکی.']];
    }

    private function kpiContent(): string
    {
        return '<h2>شاخص‌های کلیدی</h2><p>تعداد فایل فعال، نرخ تبدیل، درآمد ماهانه و عملکرد هر مشاور.</p>';
    }

    private function salesContent(): string
    {
        return <<<'HTML'
<h2>قیف فروش ملک</h2>
<p>در <strong>CRM املاک</strong> پوشه، هر سرنخ از مرحله تماس اول تا قرارداد نهایی قابل پیگیری است.</p>
<h2>مراحل فروش ملک</h2>
<ul>
<li>سرنخ و تماس اول</li>
<li>بازدید و تطبیق فایل</li>
<li>مذاکره و پیشنهاد قیمت</li>
<li>قرارداد و تحویل</li>
</ul>
<h2>نتیجه</h2>
<p>با <strong>فایلینگ املاک</strong> دقیق و CRM یکپارچه، نرخ تبدیل فروش ملک بالا می‌رود.</p>
HTML;
    }

    private function salesFaq(): array
    {
        return [
            ['question' => 'CRM چطور به فروش ملک کمک می‌کند؟', 'answer' => 'با پیگیری منظم سرنخ، یادآور بازدید و گزارش قیف فروش.'],
        ];
    }

    private function rentalContent(): string
    {
        return <<<'HTML'
<h2>مدیریت اجاره ملک</h2>
<p>ثبت فایل اجاره با جزئیات رهن و اجاره، مالک و مستأجر در <strong>فایلینگ املاک</strong>.</p>
<h2>اجاره‌نامه دیجیتال</h2>
<p>قالب اجاره‌نامه با تاریخ شمسی و خروجی PDF.</p>
<h2>پیگیری تمدید</h2>
<p>یادآور پایان قرارداد اجاره ملک برای تمدید یا فروش.</p>
HTML;
    }

    private function rentalFaq(): array
    {
        return [
            ['question' => 'آیا اجاره ملک و فروش در یک CRM است؟', 'answer' => 'بله، پوشه هر دو نوع معامله را در یک پنل مدیریت می‌کند.'],
        ];
    }

    private function accountingSoftwareContent(): string
    {
        return <<<'HTML'
<h2>حسابداری املاک یعنی چه؟</h2>
<p><strong>حسابداری املاک</strong> ثبت درآمد معاملات، هزینه‌های دفتر و کمیسیون مشاوران است.</p>
<h2>چرا نرم‌افزار؟</h2>
<p>اکسل خطا دارد و گزارش لحظه‌ای نمی‌دهد. پوشه حسابداری را به CRM و فایلینگ وصل می‌کند.</p>
<h2>گزارش مالی</h2>
<p>درآمد ماهانه، سهم هر مشاور و سود خالص دفتر در یک داشبورد.</p>
HTML;
    }
}
