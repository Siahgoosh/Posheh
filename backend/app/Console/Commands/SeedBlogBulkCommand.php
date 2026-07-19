<?php

namespace App\Console\Commands;

use App\Models\BlogPost;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class SeedBlogBulkCommand extends Command
{
    protected $signature = 'blog:seed-bulk {--count=100}';

    protected $description = 'Seed SEO blog posts with cover images (min ~400 words each)';

    /** @var array<string, string> */
    private array $categories = [
        'crm' => 'CRM املاک',
        'filing' => 'فایلینگ',
        'software' => 'نرم‌افزار املاک',
        'accounting' => 'حسابداری املاک',
        'contracts' => 'قراردادها',
        'website' => 'وبسایت املاک',
        'bots' => 'ربات و اتوماسیون',
        'digital' => 'تحول دیجیتال',
        'reports' => 'گزارش و KPI',
        'mobile' => 'اپ موبایل املاک',
    ];

    public function handle(): int
    {
        $count = (int) $this->option('count');
        $dir = storage_path('app/public/blog');
        File::ensureDirectoryExists($dir);

        $topics = $this->topics();
        $created = 0;

        for ($i = 0; $i < min($count, count($topics)); $i++) {
            [$cat, $title] = $topics[$i];
            $slug = Str::slug($title, '-', 'fa');
            if ($slug === '') {
                $slug = 'post-'.($i + 1);
            }
            $slug = $this->uniqueSlug($slug, $i + 1);
            $cover = $this->writeCover($dir, $slug, $title);
            $content = $this->buildContent($title, $cat, $slug);

            BlogPost::updateOrCreate(
                ['slug' => $slug],
                [
                    'title' => $title,
                    'excerpt' => "راهنمای تخصصی {$title} برای مشاوران و مدیران دفاتر املاک در ایران — پوشه.",
                    'content' => $content,
                    'cover_image' => $cover,
                    'meta_title' => $title.' | پوشه',
                    'meta_description' => "مقاله تخصصی درباره {$title} — نرم‌افزار پوشه برای مدیریت حرفه‌ای املاک.",
                    'keywords' => implode(', ', [$cat, 'نرم افزار املاک', 'مشاور املاک', 'پوشه']),
                    'category_slug' => $cat,
                    'category_label' => $this->categories[$cat] ?? $cat,
                    'pillar_slug' => 'pillar-'.$cat,
                    'author_name' => 'تیم پوشه',
                    'reading_time' => 6,
                    'faq' => [
                        ['question' => "چرا {$title} مهم است؟", 'answer' => 'در بازار رقابتی املاک ایران، ابزار دیجیتال و فرآیند استاندارد باعث سرعت، شفافیت و فروش بیشتر می‌شود.'],
                        ['question' => 'پوشه چه کمکی می‌کند؟', 'answer' => 'پوشه فایلینگ، CRM، حسابداری و گزارش را در یک پنل ابری برای وب، اندروید و ویندوز یکپارچه می‌کند.'],
                    ],
                    'cta_text' => 'شروع ۳ روز رایگان پنل فردی',
                    'cta_url' => '/register',
                    'is_published' => true,
                    'published_at' => now()->subDays($count - $i),
                ],
            );
            $created++;
        }

        $this->info("Seeded {$created} blog posts.");

        return self::SUCCESS;
    }

    private function uniqueSlug(string $slug, int $index): string
    {
        $base = Str::limit($slug, 80, '');
        $candidate = $base ?: 'post-'.$index;
        $n = 0;
        while (BlogPost::where('slug', $candidate)->where('slug', '!=', $slug)->exists()) {
            $n++;
            $candidate = $base.'-'.$n;
        }

        return $candidate;
    }

    private function writeCover(string $dir, string $slug, string $title): string
    {
        $file = "{$slug}-hero.svg";
        $hue = crc32($slug) % 360;
        $safe = htmlspecialchars(Str::limit($title, 60), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="630" viewBox="0 0 1200 630">
  <defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1"><stop offset="0%" stop-color="hsl({$hue},65%,42%)"/><stop offset="100%" stop-color="hsl({$hue},55%,28%)"/></linearGradient></defs>
  <rect width="1200" height="630" fill="url(#g)"/>
  <text x="80" y="300" fill="#fff" font-family="Tahoma,sans-serif" font-size="42" font-weight="700">{$safe}</text>
  <text x="80" y="360" fill="#ffffffcc" font-family="Tahoma,sans-serif" font-size="24">پوشه — سامانه مدیریت املاک</text>
</svg>
SVG;
        File::put("{$dir}/{$file}", $svg);

        return "/storage/blog/{$file}";
    }

    private function buildContent(string $title, string $category, string $slug): string
    {
        $label = $this->categories[$category] ?? $category;
        $img = "/storage/blog/{$slug}-hero.svg";

        return <<<HTML
<h2>مقدمه: چرا {$title}؟</h2>
<p>در دفاتر املاک امروز ایران، موضوع «{$title}» دیگر یک انتخاب لوکس نیست؛ بلکه شرط بقا در بازار پررقابت است. مشاورانی که همچنان به روش‌های دستی و پراکنده تکیه می‌کنند، زمان زیادی را صرف پیگیری‌های تکراری، خطاهای ثبت اطلاعات و از دست دادن فرصت فروش می‌کنند. سامانه ابری <strong>پوشه</strong> با تمرکز بر {$label}، این چالش‌ها را به فرصت تبدیل می‌کند.</p>
<img src="{$img}" alt="{$title} — تصویر شاخص مقاله پوشه" loading="lazy" width="1200" height="630" style="max-width:100%;border-radius:12px;margin:16px 0" />
<h2>چالش‌های رایج در دفاتر املاک</h2>
<p>بسیاری از آژانس‌ها هنوز اطلاعات ملک، مالک و مشتری را در فایل‌های جدا نگه می‌دارند. نتیجه، دوباره‌کاری، ناسازگاری قیمت‌ها، گم شدن تاریخچه بازدیدها و ضعف در گزارش‌دهی به مدیر دفتر است. وقتی {$title} به‌صورت سیستماتیک اجرا نشود، حتی مشاوران با تجربه هم نمی‌توانند ظرفیت واقعی خود را نشان دهند.</p>
<p>از سوی دیگر، مشتریان انتظار پاسخ سریع، پیشنهادهای دقیق و شفافیت در قرارداد دارند. هر تأخیر در پیگیری سرنخ، مستقیماً به معنای از دست رفتن کمیسیون است. اینجاست که ترکیب فایلینگ استاندارد، CRM و گزارش KPI اهمیت پیدا می‌کند.</p>
<h2>راهکار پوشه برای {$label}</h2>
<p>پوشه تمام داده‌های دفتر را در یک پنل واحد جمع می‌کند: ثبت ملک با کد یکتا، جستجوی پیشرفته، مدیریت مالک و مشتری، بازدیدها، قراردادها و حسابداری. برای {$title}، می‌توانید از فیلدهای پویا، برچسب‌ها، یادآورها و خروجی PDF/اکسل استفاده کنید تا هیچ پرونده‌ای رها نشود.</p>
<p>نسخه اندروید و وب کاملاً همگام هستند؛ مشاور در مسیر بازدید هم به پرونده ملک دسترسی دارد. مدیر دفتر نیز از داشبورد، عملکرد تیم، نرخ تبدیل سرنخ و وضعیت اشتراک را می‌بیند. این شفافیت، تصمیم‌گیری را سریع‌تر و قابل اتکاتر می‌کند.</p>
<h2>گام‌های عملی پیاده‌سازی</h2>
<ul>
<li>استانداردسازی فرم ثبت ملک و آموزش تیم در همان روز اول</li>
<li>تعریف مراحل CRM و مسئول پیگیری هر سرنخ</li>
<li>بررسی هفتگی گزارش KPI و اصلاح فرآیند فروش</li>
<li>استفاده از یادآور SMS برای تمدید اشتراک و پیگیری مشتری</li>
<li>انتشار آگهی‌های منتخب در وبسایت اختصاصی دفتر (پلن حرفه‌ای)</li>
</ul>
<p>با این چرخه، {$title} از یک شعار تبدیل به عادت روزانه تیم می‌شود. نتیجه، کاهش خطا، افزایش رضایت مالک و مشتری، و رشد پایدار درآمد دفتر است.</p>
<h2>جمع‌بندی</h2>
<p>اگر به دنبال حرفه‌ای‌تر شدن در حوزه {$label} هستید، شروع با پنل مشاور مستقل پوشه — <strong>۳ روز رایگان</strong> — منطقی‌ترین قدم است. پس از آن می‌توانید با توجه به اندازه تیم، به پلن دفتر یا حرفه‌ای ارتقا دهید. همین امروز ثبت‌نام کنید و تفاوت را در اولین هفته احساس کنید.</p>
HTML;
    }

    /** @return list<array{0: string, 1: string}> */
    private function topics(): array
    {
        $out = [];
        $templates = [
            'crm' => ['قیف فروش CRM در املاک', 'مدیریت سرنخ مشاور', 'تبدیل تماس به قرارداد', 'پیگیری خودکار مشتری', 'امتیازدهی سرنخ املاک', 'CRM برای تیم ۵ نفره', 'گزارش نرخ تبدیل مشاوران', 'اتوماسیون پیامک CRM', 'تفکیک مشتری فعال و سرد', 'بازگشت مشتری راکد'],
            'filing' => ['ثبت حرفه‌ای ملک', 'کد یکتا فایلینگ', 'عکس و مدیا ملک', 'انقضای فایل املاک', 'جستجوی پیشرفته ملک', 'برچسب‌گذاری فایل‌ها', 'اشتراک ملک در واتساپ', 'QR معرفی ملک', 'کنترل کیفیت آگهی', 'آرشیو فایل‌های قدیمی'],
            'software' => ['مهاجرت از اکسل', 'سامانه ابری املاک', 'امنیت داده دفتر', 'پشتیبان‌گیری ابری', 'همگام وب و موبایل', 'ورود OTP امن', 'نقش‌ها و دسترسی تیم', 'مقایسه نرم‌افزارهای املاک', 'دیجیتال کردن دفتر سنتی', 'ROI نرم‌افزار املاک'],
            'accounting' => ['کمیسیون مشاور', 'درآمد و هزینه دفتر', 'گزارش مالی ماهانه', 'تسویه چند مشاور', 'فاکتور و رسید', 'کنترل بدهی مالک', 'بودجه‌بندی دفتر', 'مالیات و مستندات', 'تحلیل سودآوری فایل', 'حسابداری چند شعبه'],
            'contracts' => ['مبایعه‌نامه فرم ۱۲۵', 'قرارداد اجاره رسمی', 'خروجی PDF قرارداد', 'بندهای حیاتی قرارداد', 'امضای دیجیتال', 'بایگانی قراردادها', 'یادآور سررسید', 'قالب قرارداد سفارشی', 'اختلاف طرفین', 'قرارداد مشارکت'],
            'website' => ['سایت اختصاصی دفتر', 'ساب‌دامین posheapp', 'سئو آگهی ملک', 'درخواست بازدید آنلاین', 'نقشه ملک در سایت', 'گالری تصاویر حرفه‌ای', 'اتصال سایت به CRM', 'برندینگ دفتر', 'لینک در اینستاگرام', 'تبدیل بازدیدکننده به سرنخ'],
            'bots' => ['ربات تلگرام املاک', 'ربات واتساپ', 'ارسال خودکار فایل', 'پاسخگوی اولیه مشتری', 'اتصال ربات به فایلینگ', 'مدیریت کانال تلگرام', 'قوانین پیام خودکار', 'ربات بدون کدنویسی', 'آمار تعامل ربات', 'امنیت توکن ربات'],
            'digital' => ['تحول دیجیتال آژانس', 'آموزش تیم دیجیتال', 'شاخص‌های بلوغ دیجیتال', 'فرهنگ داده‌محور', 'کاهش وابستگی به کاغذ', 'مدیریت تغییر', 'انتخاب ابزار مناسب', 'خطاهای رایج دیجیتال', 'برنامه ۹۰ روزه', 'رقابت با استارتاپ‌ها'],
            'reports' => ['داشبورد KPI', 'گزارش عملکرد مشاور', 'تحلیل منطقه‌ای', 'پیش‌بینی درآمد', 'گزارش بازدیدها', 'نمودار فایل فعال', 'خروجی اکسل مدیریتی', 'گزارش هفتگی مدیر', 'شاخص رضایت مشتری', 'Benchmark دفاتر'],
            'mobile' => ['اپ اندروید مشاور', 'کار میدانی با موبایل', 'ثبت بازدید در محل', 'آفلاین و همگام‌سازی', 'امنیت اپ املاک', 'تمدید اشتراک موبایل', 'نوتیفیکیشن مشاور', 'آموزش اپ به تیم', 'نسخه ویندوز دفتر', 'مقایسه اپ و وب'],
        ];

        foreach ($templates as $cat => $titles) {
            foreach ($titles as $t) {
                $out[] = [$cat, $t.' — راهنمای ۱۴۰۴'];
            }
        }

        return $out;
    }
}
