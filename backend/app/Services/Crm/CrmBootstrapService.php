<?php

namespace App\Services\Crm;

use App\Models\CrmLostReason;
use App\Models\CrmPipelineStage;
use App\Models\CrmScoreRule;
use App\Models\CrmSource;
use App\Models\Office;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CrmBootstrapService
{
    /** @return list<array{key:string,label:string,sort_order:int,is_won?:bool,is_lost?:bool,color?:string}> */
    public function defaultStages(): array
    {
        return [
            ['key' => 'lead', 'label' => 'سرنخ', 'sort_order' => 10, 'color' => 'slate'],
            ['key' => 'contact', 'label' => 'تماس', 'sort_order' => 20, 'color' => 'blue'],
            ['key' => 'visit', 'label' => 'بازدید', 'sort_order' => 30, 'color' => 'cyan'],
            ['key' => 'negotiation', 'label' => 'مذاکره', 'sort_order' => 40, 'color' => 'amber'],
            ['key' => 'closed_won', 'label' => 'موفق', 'sort_order' => 50, 'is_won' => true, 'color' => 'emerald'],
            ['key' => 'closed_lost', 'label' => 'ناموفق', 'sort_order' => 60, 'is_lost' => true, 'color' => 'red'],
        ];
    }

    /** @return list<array{key:string,label:string,sort_order:int}> */
    public function defaultSources(): array
    {
        return [
            ['key' => 'instagram', 'label' => 'اینستاگرام', 'sort_order' => 10],
            ['key' => 'telegram', 'label' => 'تلگرام', 'sort_order' => 20],
            ['key' => 'whatsapp', 'label' => 'واتساپ', 'sort_order' => 30],
            ['key' => 'website', 'label' => 'وب‌سایت', 'sort_order' => 40],
            ['key' => 'divar', 'label' => 'دیوار', 'sort_order' => 50],
            ['key' => 'sheypoor', 'label' => 'شیپور', 'sort_order' => 60],
            ['key' => 'phone', 'label' => 'تماس تلفنی', 'sort_order' => 70],
            ['key' => 'walk_in', 'label' => 'مراجعه حضوری', 'sort_order' => 80],
            ['key' => 'referral', 'label' => 'معرفی', 'sort_order' => 90],
            ['key' => 'existing_customer', 'label' => 'مشتری قبلی', 'sort_order' => 100],
            ['key' => 'advertisement', 'label' => 'تبلیغات', 'sort_order' => 110],
            ['key' => 'google', 'label' => 'گوگل', 'sort_order' => 120],
            ['key' => 'other', 'label' => 'سایر', 'sort_order' => 130],
        ];
    }

    /** @return list<array{key:string,label:string,points:int}> */
    public function defaultScoreRules(): array
    {
        return [
            ['key' => 'has_phone', 'label' => 'شماره موبایل', 'points' => 10],
            ['key' => 'has_budget', 'label' => 'بودجه مشخص', 'points' => 10],
            ['key' => 'has_property_type', 'label' => 'نوع ملک مشخص', 'points' => 10],
            ['key' => 'has_location', 'label' => 'منطقه مشخص', 'points' => 10],
            ['key' => 'has_value', 'label' => 'ارزش معامله', 'points' => 10],
            ['key' => 'visit_scheduled', 'label' => 'بازدید برنامه‌ریزی‌شده', 'points' => 15],
            ['key' => 'visit_completed', 'label' => 'بازدید انجام‌شده', 'points' => 15],
            ['key' => 'negotiation', 'label' => 'شروع مذاکره', 'points' => 10],
            ['key' => 'offer_submitted', 'label' => 'پیشنهاد قیمت', 'points' => 10],
            ['key' => 'no_response', 'label' => 'عدم پاسخ', 'points' => -10],
            ['key' => 'invalid_info', 'label' => 'اطلاعات نامعتبر', 'points' => -10],
            ['key' => 'missed_calls', 'label' => 'تماس‌های بی‌پاسخ مکرر', 'points' => -15],
            ['key' => 'long_inactivity', 'label' => 'عدم فعالیت طولانی', 'points' => -20],
        ];
    }

    /** @return list<array{key:string,label:string,sort_order:int}> */
    public function defaultLostReasons(): array
    {
        return [
            ['key' => 'price_too_high', 'label' => 'قیمت بالا', 'sort_order' => 10],
            ['key' => 'no_budget', 'label' => 'عدم بودجه', 'sort_order' => 20],
            ['key' => 'bought_elsewhere', 'label' => 'خرید از جای دیگر', 'sort_order' => 30],
            ['key' => 'rent_elsewhere', 'label' => 'اجاره از جای دیگر', 'sort_order' => 40],
            ['key' => 'not_responding', 'label' => 'عدم پاسخگویی', 'sort_order' => 50],
            ['key' => 'not_suitable', 'label' => 'ملک نامناسب', 'sort_order' => 60],
            ['key' => 'changed_decision', 'label' => 'انصراف', 'sort_order' => 70],
            ['key' => 'competitor', 'label' => 'رقیب', 'sort_order' => 80],
            ['key' => 'other', 'label' => 'سایر', 'sort_order' => 90],
        ];
    }

    public function ensureDefaultsForOffice(int $officeId): void
    {
        DB::transaction(function () use ($officeId) {
            foreach ($this->defaultStages() as $row) {
                CrmPipelineStage::query()->updateOrCreate(
                    ['office_id' => $officeId, 'key' => $row['key']],
                    [
                        'label' => $row['label'],
                        'sort_order' => $row['sort_order'],
                        'color' => $row['color'] ?? null,
                        'is_won' => (bool) ($row['is_won'] ?? false),
                        'is_lost' => (bool) ($row['is_lost'] ?? false),
                        'is_active' => true,
                        'is_system' => true,
                    ]
                );
            }

            foreach ($this->defaultSources() as $row) {
                CrmSource::query()->updateOrCreate(
                    ['office_id' => $officeId, 'key' => $row['key']],
                    [
                        'label' => $row['label'],
                        'sort_order' => $row['sort_order'],
                        'is_active' => true,
                        'is_system' => true,
                    ]
                );
            }

            foreach ($this->defaultScoreRules() as $row) {
                CrmScoreRule::query()->updateOrCreate(
                    ['office_id' => $officeId, 'key' => $row['key']],
                    [
                        'label' => $row['label'],
                        'points' => $row['points'],
                        'is_active' => true,
                    ]
                );
            }

            foreach ($this->defaultLostReasons() as $row) {
                CrmLostReason::query()->updateOrCreate(
                    ['office_id' => $officeId, 'key' => $row['key']],
                    [
                        'label' => $row['label'],
                        'sort_order' => $row['sort_order'],
                        'is_active' => true,
                    ]
                );
            }
        });

        // Phase 2 sales engine defaults (safe if tables missing)
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('crm_matching_weights')) {
                app(PropertyMatchingService::class)->ensureWeights($officeId);
            }
            if (\Illuminate\Support\Facades\Schema::hasTable('crm_automation_rules')) {
                app(CrmAutomationService::class)->ensureDefaultRules($officeId);
            }
        } catch (\Throwable) {
        }

        // Phase 3 intelligence defaults
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('crm_pipeline_probabilities')) {
                app(CrmIntelligenceService::class)->ensureProbabilities($officeId);
            }
            if (\Illuminate\Support\Facades\Schema::hasTable('crm_integrations')) {
                app(CrmCommunicationService::class)->ensureIntegrationStubs($officeId);
            }
            if (\Illuminate\Support\Facades\Schema::hasTable('crm_onboarding_checklist')) {
                app(CrmCommunicationService::class)->ensureOnboarding($officeId);
            }
            if (\Illuminate\Support\Facades\Schema::hasTable('ai_prompt_templates')) {
                app(\App\Services\Ai\AiService::class)->ensureDefaultPrompts($officeId);
            }
        } catch (\Throwable) {
        }
    }

    public function ensureForUser(User $user): void
    {
        if ($user->office_id) {
            $this->ensureDefaultsForOffice((int) $user->office_id);
        }
    }

    public function ensureForOffice(Office $office): void
    {
        $this->ensureDefaultsForOffice((int) $office->id);
    }
}
