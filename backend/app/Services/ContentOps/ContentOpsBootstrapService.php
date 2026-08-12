<?php

namespace App\Services\ContentOps;

use App\Models\Content\ContentAiCostLimit;
use App\Models\Content\ContentAiTaskConfig;
use App\Models\Content\ContentStyleProfile;
use Illuminate\Support\Facades\Schema;

class ContentOpsBootstrapService
{
    public function ensureDefaults(): array
    {
        if (! Schema::hasTable('content_ai_task_configs')) {
            return ['ok' => false, 'message' => 'Migration required'];
        }

        $tasks = [
            ['brief', 'Content Brief', 'local'],
            ['outline', 'Outline', 'local'],
            ['draft', 'AI Draft', 'local'],
            ['research', 'Research', 'local'],
            ['seo_audit', 'SEO Audit', 'local'],
            ['fact_check', 'Fact Check', 'local'],
            ['internal_linking', 'Internal Linking', 'local'],
            ['image_suggestion', 'Image Suggestion', 'local'],
            ['refresh', 'Content Refresh', 'local'],
            ['repurpose', 'Repurpose', 'local'],
        ];
        foreach ($tasks as [$key, $label, $provider]) {
            ContentAiTaskConfig::query()->firstOrCreate(
                ['task_key' => $key],
                [
                    'label' => $label,
                    'provider' => $provider,
                    'model' => 'local-heuristic-v1',
                    'temperature' => 0.30,
                    'max_tokens' => 2000,
                    'system_prompt' => 'Persian-first Posheh content assistant. Never invent facts, prices, laws, reviews, or sources. Never auto-publish.',
                    'timeout_sec' => 90,
                    'retry_max' => 3,
                    'cost_limit_per_job' => 0,
                    'is_active' => true,
                ]
            );
        }

        foreach (['daily' => 500000, 'monthly' => 5000000, 'per_article' => 200000, 'per_user' => 200000] as $scope => $limit) {
            ContentAiCostLimit::query()->firstOrCreate(
                ['scope' => $scope],
                ['limit_toman' => $limit, 'limit_tokens' => 0, 'is_active' => false, 'meta' => ['note' => 'Disabled by default — enable after setting real budget']]
            );
        }

        ContentStyleProfile::query()->firstOrCreate(
            ['slug' => 'posheh-default'],
            [
                'name' => 'پوشه — پیش‌فرض',
                'tone' => 'حرفه‌ای، مستقیم، بدون اغراق',
                'sentence_length' => 'متوسط',
                'vocabulary' => 'فارسی ساده مشاور املاک / SaaS',
                'formality' => 'نیمه‌رسمی',
                'cta_style' => 'دعوت به اقدام عملی بدون فشار',
                'persian_terminology' => "مشاور املاک، فایل، رهن، اجاره، کمیسیون، CRM، تور مجازی",
                'anti_patterns' => [
                    'مقدمه‌های کلیشه‌ای',
                    'جمع‌بندی مصنوعی',
                    'تکرار keyword',
                    'آمار جعلی',
                ],
                'is_default' => true,
                'is_active' => true,
            ]
        );

        return [
            'ok' => true,
            'tasks' => ContentAiTaskConfig::count(),
            'limits' => ContentAiCostLimit::count(),
            'styles' => ContentStyleProfile::count(),
            'note' => 'AI remains assistant-only. Auto-publish off by default.',
        ];
    }
}
