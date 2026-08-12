<?php

namespace App\Services\Cro;

use App\Models\Cro\CroCta;
use App\Models\Cro\CroCtaRule;
use App\Models\Cro\CroPageGoal;
use Illuminate\Support\Facades\Schema;

class CroBootstrapService
{
    public function ensureDefaults(): void
    {
        if (! Schema::hasTable('cro_ctas')) {
            return;
        }

        $defs = [
            [
                'key' => 'default-soft',
                'title' => 'مطالب مرتبط را ادامه دهید',
                'description' => 'بدون فشار تبلیغاتی',
                'button_text' => 'مشاهده وبلاگ',
                'url' => '/blog',
                'type' => 'soft',
                'funnel_stage' => 'TOFU',
                'intent' => 'informational',
                'priority' => 200,
            ],
            [
                'key' => 'product-soft',
                'title' => 'آشنایی کوتاه با پوشه',
                'description' => 'برای مشاوران و دفاتر املاک — بدون اغراق',
                'button_text' => 'آشنایی با پوشه',
                'url' => '/register',
                'type' => 'product',
                'funnel_stage' => 'MOFU',
                'intent' => 'commercial',
                'category' => 'crm',
                'priority' => 50,
            ],
            [
                'key' => 'contact-consult',
                'title' => 'سوال دارید؟',
                'description' => 'درخواست خود را ثبت کنید تا پیگیری شود',
                'button_text' => 'ثبت درخواست',
                'url' => '/contact#lead-form',
                'type' => 'contact',
                'funnel_stage' => 'BOFU',
                'intent' => 'transactional',
                'priority' => 40,
            ],
            [
                'key' => 'filing-related',
                'title' => 'فایلینگ استاندارد را جدی بگیرید',
                'description' => 'از ثبت ملک تا پیگیری مشتری',
                'button_text' => 'راهنمای فایلینگ',
                'url' => '/blog/property-filing-tips-for-agents',
                'type' => 'soft',
                'category' => 'filing',
                'funnel_stage' => 'TOFU',
                'priority' => 60,
            ],
            [
                'key' => 'accounting-product',
                'title' => 'کمیسیون و حسابداری دفتر',
                'description' => 'شفافیت تسویه مشاوران',
                'button_text' => 'آشنایی با ماژول حسابداری',
                'url' => '/register',
                'type' => 'product',
                'category' => 'accounting',
                'intent' => 'commercial',
                'funnel_stage' => 'MOFU',
                'priority' => 55,
            ],
        ];

        foreach ($defs as $def) {
            CroCta::query()->updateOrCreate(['key' => $def['key']], $def + ['is_active' => true]);
        }

        $product = CroCta::query()->where('key', 'product-soft')->first();
        $contact = CroCta::query()->where('key', 'contact-consult')->first();
        $filing = CroCta::query()->where('key', 'filing-related')->first();
        $accounting = CroCta::query()->where('key', 'accounting-product')->first();

        $rules = [
            ['name' => 'CRM commercial → product', 'cta' => $product, 'field' => 'category', 'value' => 'crm'],
            ['name' => 'software commercial → product', 'cta' => $product, 'field' => 'category', 'value' => 'software'],
            ['name' => 'digital → product', 'cta' => $product, 'field' => 'category', 'value' => 'digital'],
            ['name' => 'filing → filing guide', 'cta' => $filing, 'field' => 'category', 'value' => 'filing'],
            ['name' => 'accounting → accounting CTA', 'cta' => $accounting, 'field' => 'category', 'value' => 'accounting'],
            ['name' => 'transactional intent → contact', 'cta' => $contact, 'field' => 'intent', 'value' => 'transactional'],
        ];
        foreach ($rules as $i => $rule) {
            if (! $rule['cta']) {
                continue;
            }
            CroCtaRule::query()->updateOrCreate(
                ['name' => $rule['name']],
                [
                    'cro_cta_id' => $rule['cta']->id,
                    'match_field' => $rule['field'],
                    'match_operator' => 'eq',
                    'match_value' => $rule['value'],
                    'priority' => 10 + $i,
                    'is_active' => true,
                ]
            );
        }

        foreach ([
            ['/blog', 'Discover content', 'Engage', 'default-soft', null, 'engagement', 'TOFU', 40],
            ['/contact', 'Capture lead', 'Trust', 'contact-consult', null, 'lead', 'BOFU', 90],
            ['/register', 'Start trial', 'Educate', 'product-soft', null, 'signup', 'BOFU', 95],
            ['/about', 'Build trust', 'Contact', 'contact-consult', 'default-soft', 'trust', 'MOFU', 70],
        ] as $g) {
            CroPageGoal::query()->updateOrCreate(
                ['path' => $g[0]],
                [
                    'primary_goal' => $g[1],
                    'secondary_goal' => $g[2],
                    'primary_cta_key' => $g[3],
                    'secondary_cta_key' => $g[4],
                    'conversion_type' => $g[5],
                    'funnel_stage' => $g[6],
                    'business_value' => $g[7],
                ]
            );
        }
    }
}
