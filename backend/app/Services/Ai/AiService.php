<?php

namespace App\Services\Ai;

use App\Models\AiPromptTemplate;
use App\Models\AiUsageLog;
use App\Models\Customer;
use App\Models\CrmDeal;
use App\Models\CrmNeedProfile;
use App\Models\Property;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

/**
 * AI-ready service layer (Level 1 rule-based + Level 2 assisted stubs).
 * Never sends PII to external providers without explicit office permission & provider config.
 * No hallucination: all facts come from DB fields only.
 */
class AiService
{
    public function __construct(
        private readonly AiProviderManager $providers,
    ) {}

    public function customerSummary(User $user, Customer $customer): array
    {
        $this->assertSameOffice($user, $customer->office_id);
        $profile = Schema::hasTable('crm_need_profiles')
            ? CrmNeedProfile::where('customer_id', $customer->id)->first()
            : null;
        $deal = CrmDeal::where('office_id', $user->office_id)
            ->where('customer_id', $customer->id)
            ->orderByDesc('updated_at')
            ->first();

        $intent = match (true) {
            ($customer->lead_score ?? 0) >= 80 || ($deal?->lead_score ?? 0) >= 80 => 'high',
            ($customer->lead_score ?? 0) >= 50 => 'medium',
            default => 'low',
        };

        $summary = [
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'mobile' => $this->maskMobile($customer->mobile),
            ],
            'intent' => $intent,
            'budget' => [
                'min' => $profile?->budget_min ?? $customer->budget_min,
                'max' => $profile?->budget_max ?? $customer->budget_max,
            ],
            'preferred_area' => $profile?->preferred_locations
                ?? array_filter([$customer->preferred_city, $customer->preferred_district]),
            'last_activity' => optional($customer->last_contacted_at)?->toIso8601String(),
            'main_objection' => null,
            'recommended_action' => $intent === 'high'
                ? 'تماس فوری و ارسال فایل‌های Match با امتیاز بالا'
                : 'تکمیل Need Profile و تعیین Follow-up',
            'confidence' => 'rule_based',
            'confidence_used' => ['customer', 'need_profile', 'deal.lead_score'],
            'confidence' => 0.7,
            'provider' => 'local',
        ];

        $this->logUsage($user, 'customer_summary', 'mock', 0, ['customer_id' => $customer->id]);

        return $summary;
    }

    public function generateMessage(User $user, array $context): array
    {
        $purpose = $context['purpose'] ?? 'followup';
        $customerName = $context['customer_name'] ?? 'مشتری گرامی';
        $propertyTitle = $context['property_title'] ?? null;
        $price = $context['price'] ?? null;

        // Fact-only templates — never invent property attributes
        $body = match ($purpose) {
            'intro' => $propertyTitle
                ? "سلام {$customerName}، فایل «{$propertyTitle}»".($price ? " با قیمت ".number_format((int) $price)." تومان" : '')." مطابق درخواست شما پیدا شد. مایل به دریافت جزئیات هستید؟"
                : "سلام {$customerName}، برای ادامه پیگیری درخواست شما چند سؤال کوتاه دارم.",
            'viewing' => "سلام {$customerName}، برای بازدید".($propertyTitle ? " از «{$propertyTitle}»" : '')." چه زمانی برای شما مناسب‌تر است؟",
            'after_visit' => "سلام {$customerName}، از بازدید امروز متشکریم. نظرتان درباره فایل چیست تا گزینه‌های بهتری پیشنهاد دهیم؟",
            'negotiation' => "سلام {$customerName}، برای ادامه مذاکره".($propertyTitle ? " روی «{$propertyTitle}»" : '')." آماده‌ایم. پیشنهاد یا شرایط پرداخت خود را بفرمایید.",
            default => "سلام {$customerName}، جهت پیگیری درخواست شما تماس می‌گیریم. چه زمانی در دسترس هستید؟",
        };

        $this->logUsage($user, 'message_generation', 'mock', 0, ['purpose' => $purpose]);

        return [
            'message' => $body,
            'purpose' => $purpose,
            'mode' => 'rule_based',
            'facts_used' => array_filter(['customer_name', $propertyTitle ? 'property_title' : null, $price ? 'price' : null]),
            'confidence' => 0.85,
            'provider' => 'local',
            'disclaimer' => 'پیام فقط از داده‌های ثبت‌شده ساخته شده و ویژگی اختراع‌شده ندارد.',
        ];
    }

    public function listingAssist(User $user, Property $property): array
    {
        $this->assertSameOffice($user, $property->office_id);
        // Only use stored fields — never invent amenities/price
        $parts = array_filter([
            $property->property_category?->value ?? null,
            $property->area ? ((int) $property->area).' متر' : null,
            $property->rooms ? $property->rooms.' خواب' : null,
            $property->city,
            $property->district,
            $property->has_parking ? 'پارکینگ' : null,
            $property->has_elevator ? 'آسانسور' : null,
            $property->has_storage ? 'انباری' : null,
        ]);
        $price = $property->price ? number_format((int) $property->price).' تومان' : null;
        $title = $property->title ?: trim(implode(' · ', $parts));
        $short = trim(implode('، ', $parts)).($price ? " — {$price}" : '');
        $long = $short.($property->description ? "\n\n".$property->description : '');
        $caption = "🏠 {$title}\n".($price ? "💰 {$price}\n" : '').($property->city ? "📍 {$property->city}".($property->district ? ' / '.$property->district : '')."\n" : '').'برای اطلاعات بیشتر پیام دهید.';

        $this->logUsage($user, 'listing_assistant', 'mock', 0, ['property_id' => $property->id]);

        return [
            'title' => $title,
            'short_description' => $short,
            'long_description' => $long,
            'instagram_caption' => $caption,
            'telegram_caption' => $caption,
            'whatsapp_message' => "سلام، فایل {$title}".($price ? " ({$price})" : '').' موجود است.',
            'seo_description' => mb_substr($short, 0, 160),
            'mode' => 'rule_based',
            'facts_used' => $parts,
            'confidence' => 0.9,
            'provider' => 'local',
            'disclaimer' => 'AI حق اختراع ویژگی یا قیمت ندارد؛ فقط فیلدهای ثبت‌شده استفاده شد.',
        ];
    }

    public function ensureDefaultPrompts(?int $officeId = null): void
    {
        if (! Schema::hasTable('ai_prompt_templates')) {
            return;
        }
        $defaults = [
            ['key' => 'customer_summary', 'name' => 'خلاصه مشتری', 'template' => 'Summarize customer {{name}} using only: {{facts}}'],
            ['key' => 'message_followup', 'name' => 'پیام پیگیری', 'template' => 'Write a polite Persian follow-up for {{customer_name}} about {{context}}. Use only provided facts.'],
            ['key' => 'listing_copy', 'name' => 'توضیح فایل', 'template' => 'Rewrite listing from facts only: {{facts}}. Do not invent amenities or prices.'],
        ];
        foreach ($defaults as $row) {
            AiPromptTemplate::firstOrCreate(
                ['office_id' => $officeId, 'key' => $row['key'], 'version' => 1],
                [
                    'name' => $row['name'],
                    'template' => $row['template'],
                    'variables' => [],
                    'provider' => 'local',
                    'model' => 'rule-based',
                    'temperature' => 0.2,
                    'is_active' => true,
                ]
            );
        }
    }

    public function usageForOffice(User $user, int $days = 30): array
    {
        if (! Schema::hasTable('ai_usage_logs')) {
            return ['total_requests' => 0, 'by_feature' => []];
        }
        $q = AiUsageLog::where('office_id', $user->office_id)
            ->where('created_at', '>=', now()->subDays($days));

        return [
            'total_requests' => (clone $q)->count(),
            'total_tokens' => (int) (clone $q)->sum('total_tokens'),
            'by_feature' => (clone $q)->selectRaw('feature, count(*) as c')->groupBy('feature')->pluck('c', 'feature'),
            'status_breakdown' => (clone $q)->selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status'),
        ];
    }

    private function logUsage(User $user, string $feature, string $status, int $tokens, array $meta = []): void
    {
        if (! Schema::hasTable('ai_usage_logs')) {
            return;
        }
        try {
            AiUsageLog::create([
                'office_id' => $user->office_id,
                'user_id' => $user->id,
                'feature' => $feature,
                'provider' => 'local',
                'model' => 'rule-based',
                'prompt_tokens' => 0,
                'completion_tokens' => 0,
                'total_tokens' => $tokens,
                'cost_toman' => 0,
                'status' => $status,
                'meta' => $meta,
            ]);
        } catch (\Throwable) {
        }
    }

    private function assertSameOffice(User $user, mixed $officeId): void
    {
        if ((int) $user->office_id !== (int) $officeId) {
            abort(403);
        }
    }

    private function maskMobile(?string $mobile): ?string
    {
        if (! $mobile || strlen($mobile) < 7) {
            return $mobile;
        }

        return substr($mobile, 0, 4).'***'.substr($mobile, -3);
    }
}
