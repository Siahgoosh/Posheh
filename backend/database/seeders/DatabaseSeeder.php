<?php

namespace Database\Seeders;

use App\Enums\SubscriptionPlan as SubscriptionPlanEnum;
use App\Enums\UserRole;
use App\Models\Office;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(SystemSettingsSeeder::class);
        $this->call(BlogSeeder::class);
        $this->call(ContractTemplateSeeder::class);
        $this->seedPlans();
        $this->seedSuperAdmin();
        $this->seedDemoOffice();
    }

    private function seedPlans(): void
    {
        $plans = [
            [
                'slug' => 'solo',
                'panel_type' => 'solo',
                'name' => 'مشاور مستقل',
                'description' => 'پنل تک‌نفره برای مشاوران مستقل با فایلینگ حرفه‌ای',
                'max_users' => 1,
                'max_properties' => 200,
                'storage_gb' => 5,
                'monthly_price' => 490_000,
                'yearly_price' => 4_900_000,
                'trial_days' => 3,
                'features' => [
                    'filing', 'properties', 'search', 'favorites', 'crm',
                    'excel_export', 'pdf_export', 'jalali_calendar', 'saved_searches',
                ],
                'sort_order' => 1,
            ],
            [
                'slug' => 'office',
                'panel_type' => 'office',
                'name' => 'دفتر املاک',
                'description' => 'تا ۳ مشاور — حسابداری حرفه‌ای دفتر و ربات تلگرام',
                'max_users' => 3,
                'max_properties' => 500,
                'storage_gb' => 15,
                'monthly_price' => 1_490_000,
                'yearly_price' => 14_900_000,
                'trial_days' => 3,
                'features' => [
                    'filing', 'properties', 'search', 'favorites', 'crm',
                    'excel_export', 'pdf_export', 'jalali_calendar', 'saved_searches',
                    'accounting', 'team', 'telegram_bot', 'activity_logs',
                ],
                'sort_order' => 2,
            ],
            [
                'slug' => 'premium',
                'panel_type' => 'premium',
                'name' => 'دفتر حرفه‌ای',
                'description' => 'نمایش در وبسایت، تیک وریفای، ربات واتساپ و تلگرام و امکانات پیشرفته',
                'max_users' => 3,
                'max_properties' => 1000,
                'storage_gb' => 30,
                'monthly_price' => 2_990_000,
                'yearly_price' => 29_900_000,
                'trial_days' => 3,
                'features' => [
                    'filing', 'properties', 'search', 'favorites', 'crm',
                    'excel_export', 'pdf_export', 'jalali_calendar', 'saved_searches',
                    'accounting', 'team', 'telegram_bot', 'whatsapp_bot',
                    'website_listing', 'verified_badge', 'activity_logs', 'advanced_analytics',
                ],
                'sort_order' => 3,
            ],
        ];

        foreach ($plans as $plan) {
            SubscriptionPlan::updateOrCreate(['slug' => $plan['slug']], $plan);
        }

        SubscriptionPlan::whereIn('slug', ['basic', 'professional', 'unlimited'])
            ->update(['is_active' => false]);
    }

    private function seedSuperAdmin(): void
    {
        User::updateOrCreate(
            ['mobile' => '09120000000'],
            [
                'name' => 'مدیر سیستم',
                'role' => UserRole::SuperAdmin,
                'is_active' => true,
                'mobile_verified_at' => now(),
            ]
        );

        User::updateOrCreate(
            ['mobile' => '09170577873'],
            [
                'name' => 'مدیر پوشه',
                'role' => UserRole::SuperAdmin,
                'is_active' => true,
                'mobile_verified_at' => now(),
            ]
        );
    }

    private function seedDemoOffice(): void
    {
        $office = Office::updateOrCreate(
            ['slug' => 'demo-office'],
            [
                'name' => 'دفتر املاک نمونه',
                'phone' => '02112345678',
                'city' => 'تهران',
                'address' => 'خیابان ولیعصر',
                'is_active' => true,
                'trial_ends_at' => now()->addDays(14),
            ]
        );

        Wallet::updateOrCreate(['office_id' => $office->id], ['balance' => 5_000_000]);

        $plan = SubscriptionPlan::where('slug', 'office')->first();

        Subscription::updateOrCreate(
            ['office_id' => $office->id, 'status' => 'active'],
            [
                'subscription_plan_id' => $plan->id,
                'starts_at' => now(),
                'ends_at' => now()->addMonth(),
                'auto_renew' => true,
            ]
        );

        $office->update([
            'subscription_plan_id' => $plan->id,
            'panel_type' => 'office',
            'trial_ends_at' => now()->addDays(3),
        ]);

        User::updateOrCreate(
            ['mobile' => '09121111111'],
            [
                'name' => 'علی محمدی',
                'office_id' => $office->id,
                'role' => UserRole::OfficeManager,
                'is_active' => true,
                'mobile_verified_at' => now(),
            ]
        );

        User::updateOrCreate(
            ['mobile' => '09122222222'],
            [
                'name' => 'سارا احمدی',
                'office_id' => $office->id,
                'role' => UserRole::Consultant,
                'is_active' => true,
                'mobile_verified_at' => now(),
            ]
        );

        User::updateOrCreate(
            ['mobile' => '09123333333'],
            [
                'name' => 'رضا کریمی',
                'office_id' => $office->id,
                'role' => UserRole::Consultant,
                'is_active' => true,
                'mobile_verified_at' => now(),
            ]
        );
    }
}
