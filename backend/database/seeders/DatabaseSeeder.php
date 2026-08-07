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
        $this->call(CommunicationSeeder::class);
        if (class_exists(VirtualTourSeeder::class)) {
            $this->call(VirtualTourSeeder::class);
        }
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
                'description' => 'پنل تک‌نفره — فایلینگ، CRM پایه و خروجی PDF/اکسل',
                'max_users' => 1,
                'max_properties' => 150,
                'storage_gb' => 5,
                'monthly_price' => 590_000,
                'yearly_price' => 5_900_000,
                'trial_days' => 0,
                'features' => [
                    'filing', 'properties', 'search', 'favorites', 'crm',
                    'excel_export', 'pdf_export', 'jalali_calendar', 'saved_searches',
                    'property_share', 'ad_copy', 'quality_score',
                ],
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'slug' => 'office',
                'panel_type' => 'office',
                'name' => 'دفتر املاک',
                'description' => 'تا ۵ مشاور — حسابداری، تیم، ربات تلگرام و گزارش KPI',
                'max_users' => 5,
                'max_properties' => 600,
                'storage_gb' => 20,
                'monthly_price' => 990_000,
                'yearly_price' => 9_900_000,
                'trial_days' => 0,
                'features' => [
                    'filing', 'properties', 'search', 'favorites', 'crm',
                    'excel_export', 'pdf_export', 'jalali_calendar', 'saved_searches',
                    'property_share', 'ad_copy', 'quality_score', 'lead_scoring',
                    'accounting', 'team', 'telegram_bot', 'activity_logs',
                    'commissions', 'visit_calendar', 'owner_portal',
                ],
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'slug' => 'premium',
                'panel_type' => 'premium',
                'name' => 'دفتر حرفه‌ای',
                'description' => 'سایت اختصاصی، واتساپ، تیک وریفای و CRM پیشرفته با امتیازدهی سرنخ',
                'max_users' => 10,
                'max_properties' => 1500,
                'storage_gb' => 50,
                'monthly_price' => 1_690_000,
                'yearly_price' => 16_900_000,
                'trial_days' => 0,
                'features' => [
                    'filing', 'properties', 'search', 'favorites', 'crm',
                    'excel_export', 'pdf_export', 'jalali_calendar', 'saved_searches',
                    'property_share', 'ad_copy', 'quality_score', 'lead_scoring',
                    'accounting', 'team', 'telegram_bot', 'whatsapp_bot',
                    'website_listing', 'verified_badge', 'activity_logs',
                    'advanced_analytics', 'commissions', 'visit_calendar',
                    'owner_portal', 'demand_heatmap', 'property_compare',
                ],
                'sort_order' => 3,
                'is_active' => true,
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
        $defaultPassword = env('SEED_ADMIN_PASSWORD', 'Posheh@2026');

        User::updateOrCreate(
            ['mobile' => '09120000000'],
            [
                'name' => 'مدیر سیستم',
                'email' => 'admin@posheapp.ir',
                'username' => 'admin',
                'password' => $defaultPassword,
                'role' => UserRole::SuperAdmin,
                'is_active' => true,
                'mobile_verified_at' => now(),
                'email_verified_at' => now(),
            ]
        );

        User::updateOrCreate(
            ['mobile' => '09170577873'],
            [
                'name' => 'مدیر پوشه',
                'email' => 'info@posheapp.ir',
                'username' => 'posheh',
                'password' => $defaultPassword,
                'role' => UserRole::SuperAdmin,
                'is_active' => true,
                'mobile_verified_at' => now(),
                'email_verified_at' => now(),
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

        $demoPassword = env('SEED_ADMIN_PASSWORD', 'Posheh@2026');

        User::updateOrCreate(
            ['mobile' => '09121111111'],
            [
                'name' => 'علی محمدی',
                'email' => 'demo.manager@posheapp.ir',
                'username' => 'demo_manager',
                'password' => $demoPassword,
                'office_id' => $office->id,
                'role' => UserRole::OfficeManager,
                'is_active' => true,
                'mobile_verified_at' => now(),
                'email_verified_at' => now(),
            ]
        );

        User::updateOrCreate(
            ['mobile' => '09122222222'],
            [
                'name' => 'سارا احمدی',
                'email' => 'demo.consultant@posheapp.ir',
                'username' => 'demo_consultant',
                'password' => $demoPassword,
                'office_id' => $office->id,
                'role' => UserRole::Consultant,
                'is_active' => true,
                'mobile_verified_at' => now(),
                'email_verified_at' => now(),
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
