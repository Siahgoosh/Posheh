<?php

namespace App\Services\Ai;

use App\Models\AiContentGeneration;
use App\Models\Office;
use App\Models\Property;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class ContentAssistantService
{
    public function __construct(
        private readonly OfficeContextBuilder $contextBuilder,
    ) {}

    /** @param array<string, mixed> $options */
    public function generate(User $user, string $type, array $options = []): AiContentGeneration
    {
        $office = $user->office;
        abort_unless($office, 403);

        if (! $this->canUseAssistant($user, $office)) {
            throw ValidationException::withMessages([
                'plan' => ['دستیار هوشمند تولید محتوا در پلن حرفه‌ای (Premium) فعال است.'],
            ]);
        }

        $allowed = config('ai.types', []);
        if (! in_array($type, $allowed, true)) {
            throw ValidationException::withMessages(['type' => ['نوع خروجی معتبر نیست.']]);
        }

        $tone = $options['tone'] ?? 'friendly';
        $propertyId = $options['property_id'] ?? null;
        $property = $propertyId
            ? Property::where('office_id', $office->id)->withCount('media')->findOrFail($propertyId)
            : null;

        $context = $this->contextBuilder->build($office);
        if ($property) {
            $context['selected_property'] = $this->contextBuilder->propertyContext($property);
        }

        $cacheKey = 'ai:'.$office->id.':'.md5($type.$tone.($propertyId ?? '').json_encode($context['inventory']));
        $cached = Cache::get($cacheKey);

        if ($cached && ! ($options['regenerate'] ?? false)) {
            return AiContentGeneration::create([
                'office_id' => $office->id,
                'user_id' => $user->id,
                'type' => $type,
                'tone' => $tone,
                'property_id' => $propertyId,
                'input' => $options,
                'output' => $cached['output'],
                'meta' => ['reason' => $cached['reason'] ?? null, 'cached' => true],
                'provider' => $cached['provider'] ?? 'rules',
                'tokens_used' => $cached['tokens'] ?? 0,
            ]);
        }

        [$output, $reason, $provider, $tokens] = $this->dispatch($type, $context, $tone, $options);

        Cache::put($cacheKey, ['output' => $output, 'reason' => $reason, 'provider' => $provider, 'tokens' => $tokens], config('ai.cache_ttl', 3600));

        return AiContentGeneration::create([
            'office_id' => $office->id,
            'user_id' => $user->id,
            'type' => $type,
            'tone' => $tone,
            'property_id' => $propertyId,
            'input' => $options,
            'output' => $output,
            'meta' => ['reason' => $reason],
            'provider' => $provider,
            'tokens_used' => $tokens,
        ]);
    }

    /** @return array<int, AiContentGeneration> */
    public function history(User $user, int $limit = 30): array
    {
        return AiContentGeneration::where('office_id', $user->office_id)
            ->latest()
            ->limit($limit)
            ->get()
            ->all();
    }

    public function dailyBriefing(User $user): AiContentGeneration
    {
        return $this->generate($user, 'daily_plan', ['tone' => 'professional']);
    }

    private function canUseAssistant(User $user, Office $office): bool
    {
        $role = $user->role?->value ?? (string) $user->role;
        if (in_array($role, ['super_admin', 'platform_admin'], true)) {
            return true;
        }

        $office->loadMissing('plan');
        $features = $office->plan?->features ?? [];

        return in_array('content_assistant', $features, true);
    }

    /** @param array<string, mixed> $context @param array<string, mixed> $options @return array{0: string, 1: string, 2: string, 3: int} */
    private function dispatch(string $type, array $context, string $tone, array $options): array
    {
        $output = $this->ruleBasedGenerate($type, $context, $tone, $options);
        $reason = $this->buildReason($type, $context);

        return [$output, $reason, 'smart-engine', 0];
    }

    /** @param array<string, mixed> $context @param array<string, mixed> $options */
    private function ruleBasedGenerate(string $type, array $context, string $tone, array $options): string
    {
        return match ($type) {
            'reels_script' => $this->reelsScript($context, $tone),
            'content_ideas' => $this->contentIdeas($context),
            'content_calendar' => $this->contentCalendar($context),
            'story_script' => $this->storyScript($context, $tone),
            'caption' => $this->caption($context, $tone),
            'hashtags' => $this->hashtags($context),
            'publish_time' => $this->publishTime($context),
            'ad_text' => $this->adText($context, $options['channel'] ?? 'divar'),
            'whatsapp_message' => $this->whatsappMessage($context, $options['message_type'] ?? 'intro'),
            'promote_property' => $this->promoteProperty($context),
            'market_analysis' => $this->marketAnalysis($context),
            'campaign_suggestion' => $this->campaignSuggestion($context),
            'video_script' => $this->videoScript($context, (int) ($options['duration'] ?? 60)),
            'stale_property_analysis' => $this->staleAnalysis($context),
            'daily_plan' => $this->dailyPlan($context),
            'cover_text' => $this->coverText($context),
            'seasonal_campaign' => $this->seasonalCampaign($context),
            default => 'خروجی در دسترس نیست.',
        };
    }

    /** @param array<string, mixed> $context */
    private function reelsScript(array $context, string $tone): string
    {
        $p = $context['selected_property'] ?? ($context['top_properties'][0] ?? []);
        $office = $context['office']['name'];
        $district = $p['district'] ?? ($context['inventory']['top_districts'][0] ?? 'منطقه');
        $type = $p['type'] ?? 'ملک';
        $price = isset($p['price']) ? number_format($p['price']).' تومان' : 'توافقی';
        $area = $p['area'] ?? '';
        $rooms = $p['rooms'] ?? '';
        $code = $p['code'] ?? '---';
        $views = $p['views'] ?? 0;
        $city = $context['office']['city'] ?? '';

        return $this->officeHeader($context, $tone).<<<OUT
🎬 سناریوی ریلز اینستاگرام — {$office}

📌 عنوان: «{$type} خوش‌قیمت در {$district} — فرصت محدود!»

⏱ هوک ۳ ثانیه اول:
«اگه دنبال {$type} تو {$district} هستی، این ویدیو رو تا آخر ببین!»

🎙 متن گویندگی:
سلام! من از {$office} هستم. امروز می‌خوام یه {$type} فوق‌العاده در {$district} بهت معرفی کنم.
{$area} متر، {$rooms} خواب، قیمت {$price}.
این فایل {$views} بازدید داشته — یعنی تقاضاش بالاست!

🎥 ترتیب فیلمبرداری:
۱. نمای بیرونی ساختمان (۵ ثانیه)
۲. پذیرایی با نور طبیعی (۸ ثانیه)
۳. آشپزخانه و امکانات (۶ ثانیه)
۴. اتاق خواب اصلی (۶ ثانیه)
۵. نمای منطقه / دسترسی‌ها (۵ ثانیه)

✨ افکت: ترنزیشن سریع + زوم ملایم
🎵 موزیک: ترند ملایم املاک (Upbeat Persian Lo-Fi)

📝 کپشن:
{$type} {$district} | کد {$code}
برای بازدید همین الان دایرکت بدید 📩

#املاک #{$city} #{$district} #خرید_خانه

📲 CTA: «کد {$code} رو در واتساپ بفرست — هماهنگی بازدید»
OUT;
    }

    /** @param array<string, mixed> $context */
    private function contentIdeas(array $context): string
    {
        $city = $context['office']['city'] ?? 'شهر';
        $districts = implode('، ', $context['inventory']['top_districts'] ?? ['منطقه ۱']);
        $ideas = [
            "امروز بازار {$city} — قیمت‌ها بالا می‌ره یا پایین؟",
            "چرا {$districts} پرتقاضاترین منطقه ماست؟",
            "۵ اشتباه رایج خریداران اولین‌خانه",
            "قبل از امضای قرارداد این ۷ مورد رو چک کن",
            "فایل هفته: ".($context['top_properties'][0]['code'] ?? 'جدیدترین فایل'),
            "پشت صحنه {$context['office']['name']} — روز یک مشاور",
            "معرفی مشاور برتر: ".($context['performance']['top_consultant'] ?? 'تیم ما'),
            "داستان فروش موفق در {$city}",
            "مقایسه اجاره vs خرید در {$city}",
            "چک‌لیست بازدید ملک — ۱۰ نکته طلایی",
        ];

        foreach ($context['new_properties'] ?? [] as $i => $p) {
            if ($i >= 5) break;
            $ideas[] = "ریلز معرفی {$p['type']} کد {$p['code']} در {$p['district']}";
        }

        foreach ($context['inventory']['top_districts'] ?? [] as $d) {
            $ideas[] = "تور محله {$d} — مزایا و معایب";
        }

        $ideas = array_slice(array_unique($ideas), 0, 30);
        $lines = array_map(fn ($idea, $i) => ($i + 1).'. '.$idea, $ideas, array_keys($ideas));

        return "💡 ۳۰ ایده اختصاصی تولید محتوا برای {$context['office']['name']}\n\n".implode("\n", $lines);
    }

    /** @param array<string, mixed> $context */
    private function contentCalendar(array $context): string
    {
        $office = $context['office']['name'];
        $lines = ["📅 تقویم محتوای ۳۰ روزه — {$office}\n"];
        $formats = ['استوری سؤال', 'پست کاروسل', 'ریلز فایل', 'نظرسنجی', 'CTA واتساپ'];

        for ($day = 1; $day <= 30; $day++) {
            $fmt = $formats[$day % count($formats)];
            $prop = $context['top_properties'][$day % max(1, count($context['top_properties'] ?? []))] ?? null;
            $topic = $prop ? "فایل {$prop['code']} — {$prop['district']}" : "آموزش بازار {$context['office']['city']}";
            $lines[] = "روز {$day}: {$fmt} | {$topic} | ساعت پیشنهادی: ".($day % 2 === 0 ? '۲۰:۰۰' : '۱۲:۰۰');
        }

        return implode("\n", $lines);
    }

    /** @param array<string, mixed> $context */
    private function storyScript(array $context, string $tone): string
    {
        $p = $context['selected_property'] ?? ($context['new_properties'][0] ?? []);
        $ptype = $p['type'] ?? 'ملک';
        $parea = $p['area'] ?? '';
        $pdistrict = $p['district'] ?? '';
        $pcode = $p['code'] ?? '---';
        $pprice = ! empty($p['price']) ? number_format($p['price']).' تومان' : 'تماس';
        $officeName = $context['office']['name'];
        $city = $context['office']['city'] ?? '';
        $phone = $context['office']['phone'] ?? '';

        return <<<OUT
📱 سناریوی استوری ۴ قسمتی — {$officeName}

استوری ۱ (سؤال):
«تو {$city} دنبال چی می‌گردی؟ 🏠»
[استیکر نظرسنجی: خرید | اجاره | سرمایه‌گذاری]

استوری ۲ (نظرسنجی):
«بودجه شما کدوم محدوده‌ست؟»
[گزینه‌ها بر اساس میانگین قیمت منطقه]

استوری ۳ (معرفی فایل):
«{$ptype} {$parea} متری — {$pdistrict}»
کد: {$pcode} | قیمت: {$pprice}

استوری ۴ (CTA):
«برای بازدید همین الان واتساپ بده 📲»
[لینک واتساپ + شماره {$phone}]
OUT;
    }

    /** @param array<string, mixed> $context */
    private function caption(array $context, string $tone): string
    {
        $p = $context['selected_property'] ?? ($context['top_properties'][0] ?? []);
        $loc = implode('، ', array_filter([$p['city'] ?? null, $p['district'] ?? null, $p['neighborhood'] ?? null]));
        $ptype = $p['type'] ?? 'ملک';
        $parea = $p['area'] ?? '';
        $pcode = $p['code'] ?? '---';
        $prooms = $p['rooms'] ?? '';
        $pprice = ! empty($p['price']) ? number_format($p['price']).' تومان' : 'توافقی';
        $officeName = $context['office']['name'];

        return <<<OUT
✍️ کپشن‌های اختصاصی — کد {$pcode}

【احساسی】
هر خانه‌ای یه داستان داره… این {$ptype} در {$loc} منتظر صاحب جدیدشه. {$parea} متر آرامش.

【فروش】
فرصت عالی! {$ptype} {$parea} متری {$loc} — قیمت: {$pprice}. بازدید رایگان.

【لوکس】
تجربه زندگی در سطح جدید. {$loc} | {$parea} متر | امکانات کامل. فقط برای مخاطب خاص.

【کوتاه】
{$ptype} {$parea}m {$loc} | کد {$pcode} 📲

【بلند】
{$officeName} proudly presents...
{$ptype} با {$parea} متر زیربنا در {$loc}.
{$prooms} خواب | قیمت: {$pprice}
برای هماهنگی بازدید با ما تماس بگیرید.
OUT;
    }

    /** @param array<string, mixed> $context */
    private function hashtags(array $context): string
    {
        $city = str_replace(' ', '_', $context['office']['city'] ?? 'تهران');
        $districts = $context['inventory']['top_districts'] ?? [];
        $tags = ["#املاک", "#{$city}", '#خرید_خانه', '#مشاور_املاک', '#فروش_ملک'];

        foreach (array_slice($districts, 0, 5) as $d) {
            $tags[] = '#'.str_replace(' ', '_', $d);
        }

        $tags = array_merge($tags, ['#رهن_اجاره', '#سرمایه_گذاری', '#خانه_اولی', '#پوشه', '#real_estate_iran']);

        return "🏷 هشتگ‌های پیشنهادی:\n\n".implode(' ', array_unique($tags));
    }

    /** @param array<string, mixed> $context */
    private function publishTime(array $context): string
    {
        return <<<OUT
⏰ بهترین زمان انتشار برای {$context['office']['city']}

📸 ریلز: سه‌شنبه و پنج‌شنبه — ۲۰:۰۰ تا ۲۲:۰۰
📱 استوری: هر روز — ۱۰:۰۰ و ۲۱:۰۰
📝 پست: یکشنبه — ۱۲:۰۰ تا ۱۴:۰۰

دلیل: بیشترین تعامل مخاطبان املاک در شب‌های میانی هفته و ظهر آخر هفته است.
بازدید فعلی فایل‌های شما: {$context['performance']['total_views']} — زمان‌بندی منظم = رشد ۲ برابری تعامل
OUT;
    }

    /** @param array<string, mixed> $context */
    private function adText(array $context, string $channel): string
    {
        $p = $context['selected_property'] ?? ($context['top_properties'][0] ?? []);
        $loc = implode('، ', array_filter([$p['city'] ?? null, $p['district'] ?? null]));

        $title = "{$p['type'] ?? 'ملک'} {$p['area'] ?? ''} متری — {$loc}";
        $body = "کد {$p['code'] ?? ''} | ".($p['price'] ? number_format($p['price']).' تومان' : 'قیمت توافقی')."\n";
        $body .= ($p['rooms'] ?? '')." خواب | {$context['office']['name']}\n";
        $body .= "تماس: {$context['office']['phone']}";

        return "📢 متن آگهی برای {$channel}\n\nعنوان: {$title}\n\n{$body}";
    }

    /** @param array<string, mixed> $context */
    private function whatsappMessage(array $context, string $messageType): string
    {
        $p = $context['selected_property'] ?? ($context['top_properties'][0] ?? []);

        return match ($messageType) {
            'followup' => "سلام! پیگیری فایل {$p['code'] ?? ''} — آیا فرصت بازدید داشتید؟ در صورت تمایل زمان جایگزین هماهنگ می‌کنیم. — {$context['office']['name']}",
            'congrats' => "تبریک! 🎉 قرارداد شما با موفقیت ثبت شد. {$context['office']['name']} در کنار شماست.",
            'new_client' => "سلام و خوش‌آمدید! من از {$context['office']['name']} هستم. برای یافتن بهترین گزینه، بودجه و منطقه مورد نظرتون رو بفرمایید.",
            default => "سلام! {$p['type'] ?? 'ملک'} {$p['area'] ?? ''} متری در {$p['district'] ?? ''} — کد {$p['code'] ?? ''}\nقیمت: ".($p['price'] ? number_format($p['price']).' تومان' : 'تماس')."\nبرای بازدید هماهنگ کنیم؟ — {$context['office']['name']}",
        };
    }

    /** @param array<string, mixed> $context */
    private function promoteProperty(array $context): string
    {
        $lines = ["🎯 پیشنهاد فایل برای تبلیغ — {$context['office']['name']}\n"];
        foreach (array_slice($context['top_properties'] ?? [], 0, 3) as $i => $p) {
            $score = ($p['views'] ?? 0) + ($p['created_days_ago'] < 14 ? 20 : 0);
            $lines[] = ($i + 1).". کد {$p['code']} — {$p['type']} {$p['district']} | بازدید: {$p['views']} | امتیاز تبلیغ: {$score}";
        }
        $lines[] = "\nپیشنهاد: فایل با بیشترین بازدید + فایل جدید زیر ۲ هفته = بیشترین نرخ تبدیل";

        return implode("\n", $lines);
    }

    /** @param array<string, mixed> $context */
    private function marketAnalysis(array $context): string
    {
        $avg = $context['inventory']['avg_price'];
        $city = $context['office']['city'] ?? 'شهر';
        $districts = implode('، ', $context['inventory']['top_districts'] ?? []);

        return <<<OUT
📊 تحلیل بازار روزانه — {$city}

• میانگین قیمت فایل‌های فعال: {$avg ? number_format($avg).' تومان' : 'نامشخص'}
• مناطق پرتقاضا: {$districts}
• تعداد فایل فعال: {$context['inventory']['total_active']}
• کل بازدید فایل‌ها: {$context['performance']['total_views']}

🔥 مناطق داغ: {$districts}
📈 روند: تقاضا برای واحدهای میان‌رده در مناطق دسترسی‌پذیر
💡 پیشنهاد سرمایه‌گذاری: فایل‌های پیش‌فروش و رهن‌کامل در مناطق در حال توسعه
OUT;
    }

    /** @param array<string, mixed> $context */
    private function campaignSuggestion(array $context): string
    {
        return <<<OUT
🚀 پیشنهاد کمپین‌های بازاریابی — {$context['office']['name']}

۱. کمپین خانه اولی‌ها — فایل‌های زیر {$context['inventory']['avg_price'] ? number_format($context['inventory']['avg_price'] * 0.7) : '۵ میلیارد'} تومان
۲. کمپین سرمایه‌گذاری — واحدهای پیش‌فروش و تجاری
۳. کمپین لوکس — فایل‌های بالای میانگین قیمت منطقه
۴. کمپین اجاره سریع — فایل‌های رهن و اجاره با تقاضای بالا
۵. کمپین معرفی دفتر — استوری پشت صحنه + ریلز تیم
OUT;
    }

    /** @param array<string, mixed> $context */
    private function videoScript(array $context, int $duration): string
    {
        $p = $context['selected_property'] ?? ($context['top_properties'][0] ?? []);
        $sections = (int) ceil($duration / 15);

        $script = "🎬 اسکریپت ویدئو {$duration} ثانیه — کد {$p['code'] ?? ''}\n\n";
        for ($i = 1; $i <= $sections; $i++) {
            $script .= "بخش {$i} (۱۵ ثانیه): ";
            $script .= match ($i) {
                1 => "معرفی دفتر و هوک — «{$context['office']['name']} — {$p['district'] ?? ''}»\n",
                2 => "نمای بیرونی و دسترسی‌ها\n",
                3 => "فضای داخلی و امکانات — {$p['area'] ?? ''} متر\n",
                default => "CTA — «همین الان تماس بگیرید: {$context['office']['phone']}»\n",
            };
        }

        return $script;
    }

    /** @param array<string, mixed> $context */
    private function staleAnalysis(array $context): string
    {
        $lines = ["🔍 تحلیل فایل‌های راکد — {$context['office']['name']}\n"];
        foreach ($context['stale_properties'] ?? [] as $p) {
            $reasons = [];
            if (($p['views'] ?? 0) < 5) $reasons[] = 'بازدید کم';
            if (! ($p['description'] ?? '')) $reasons[] = 'توضیحات ناقص';
            if (! ($p['has_virtual_tour'] ?? false)) $reasons[] = 'بدون تور ۳۶۰';
            $lines[] = "کد {$p['code']}: ".($reasons ? implode('، ', $reasons) : 'نیاز به بازنشر')." | راهکار: به‌روزرسانی عکس + کپشن جدید + ریلز";
        }
        if (count($context['stale_properties'] ?? []) === 0) {
            $lines[] = 'فایل راکد شناسایی نشد — عالی!';
        }

        return implode("\n", $lines);
    }

    /** @param array<string, mixed> $context */
    private function dailyPlan(array $context): string
    {
        $p = $context['new_properties'][0] ?? ($context['top_properties'][0] ?? []);
        $hour = '۲۱:۰۰';

        return $this->officeHeader($context, 'professional').<<<OUT
☀️ برنامه تولید محتوای امروز — {$context['office']['name']}
تاریخ: {$this->persianDate()}

📸 امروز این فایل را فیلم بگیر:
کد {$p['code'] ?? '---'} — {$p['type'] ?? 'ملک'} {$p['district'] ?? ''}

✍️ این کپشن را منتشر کن:
{$p['type'] ?? 'ملک'} {$p['area'] ?? ''} متری {$p['district'] ?? ''} | کد {$p['code'] ?? ''}

📱 استوری: نظرسنجی «خرید یا اجاره؟» + معرفی فایل

🎵 موزیک پیشنهادی: ترند ملایم فارسی

⏰ زمان انتشار: {$hour}

📊 دلیل: فایل‌های جدید در {$hour} بیشترین بازدید را دارند.
OUT;
    }

    /** @param array<string, mixed> $context */
    private function coverText(array $context): string
    {
        $p = $context['selected_property'] ?? ($context['top_properties'][0] ?? []);

        return <<<OUT
🖼 پیشنهاد کاور ریلز

متن روی کاور: «{$p['type'] ?? 'ملک'} {$p['area'] ?? ''}m — {$p['district'] ?? ''}»
زیرنویس: «کد {$p['code'] ?? ''}»

رنگ پیشنهادی: #0f766e (سبز اعتماد) یا #b8860b (لوکس)
فونت: وزیر / ایران‌سنس Bold
ترکیب‌بندی: متن پایین چپ + لوگو بالا راست
OUT;
    }

    /** @param array<string, mixed> $context */
    private function seasonalCampaign(array $context): string
    {
        $month = (int) now()->format('n');
        $season = match (true) {
            in_array($month, [3, 4], true) => 'نوروز — کمپین خانه نو',
            in_array($month, [6, 7, 8], true) => 'تابستان — کمپین ویلا و ساحل',
            in_array($month, [9, 10], true) => 'بازگشایی مدارس — کمپین نزدیک مدارس',
            $month === 12 => 'یلدا — کمپین خانه دنج',
            default => 'فصل جاری — کمپین فایل‌های ویژه',
        };

        return "🎉 کمپین فصلی پیشنهادی: {$season}\n\nدفتر: {$context['office']['name']}\nشهر: {$context['office']['city']}\n\nایده: استوری مسابقه + ریلز فایل ویژه + تخفیف کارمزد برای قراردادهای این ماه";
    }

    /** @param array<string, mixed> $context */
    private function buildReason(string $type, array $context): string
    {
        $districts = implode('، ', array_slice($context['inventory']['top_districts'] ?? [], 0, 2));

        return "بر اساس {$context['inventory']['total_active']} فایل فعال، مناطق {$districts} و عملکرد {$context['performance']['total_views']} بازدید";
    }

    private function persianDate(): string
    {
        return now()->timezone('Asia/Tehran')->format('Y/m/d');
    }

    /** @param array<string, mixed> $context */
    private function officeHeader(array $context, string $tone): string
    {
        $name = $context['office']['name'] ?? 'دفتر املاک';
        $city = $context['office']['city'] ?? '';
        $toneLabel = match ($tone) {
            'formal' => 'رسمی',
            'luxury' => 'لوکس',
            'investment' => 'سرمایه‌گذاری',
            'educational' => 'آموزشی',
            default => 'صمیمی',
        };

        return "🏢 {$name} | 📍 {$city} | لحن: {$toneLabel} | 📅 {$this->persianDate()}\n";
    }
}
