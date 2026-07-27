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
        $this->seedPlans();
        $this->seedSuperAdmin();
        $this->seedDemoOffice();
    }

    private function seedPlans(): void
    {
        $plans = [
            [
                'slug' => SubscriptionPlanEnum::Basic->value,
                'name' => SubscriptionPlanEnum::Basic->label(),
                'description' => 'مناسب برای دفاتر کوچک با حداکثر ۳ مشاور',
                'max_users' => 3,
                'max_properties' => 100,
                'storage_gb' => 5,
                'monthly_price' => 990_000,
                'yearly_price' => 9_900_000,
                'features' => ['excel_export', 'pdf_export', 'jalali_calendar'],
                'sort_order' => 1,
            ],
            [
                'slug' => SubscriptionPlanEnum::Professional->value,
                'name' => SubscriptionPlanEnum::Professional->label(),
                'description' => 'مناسب برای دفاتر متوسط با امکانات پیشرفته',
                'max_users' => 10,
                'max_properties' => 1000,
                'storage_gb' => 25,
                'monthly_price' => 2_490_000,
                'yearly_price' => 24_900_000,
                'features' => ['excel_export', 'pdf_export', 'jalali_calendar', 'saved_searches', 'activity_logs'],
                'sort_order' => 2,
            ],
            [
                'slug' => SubscriptionPlanEnum::Unlimited->value,
                'name' => SubscriptionPlanEnum::Unlimited->label(),
                'description' => 'نامحدود برای دفاتر بزرگ',
                'max_users' => 9999,
                'max_properties' => 999999,
                'storage_gb' => 100,
                'monthly_price' => 4_990_000,
                'yearly_price' => 49_900_000,
                'features' => ['excel_export', 'pdf_export', 'jalali_calendar', 'saved_searches', 'activity_logs', 'api_access', 'white_label'],
                'sort_order' => 3,
            ],
        ];

        foreach ($plans as $plan) {
            SubscriptionPlan::updateOrCreate(['slug' => $plan['slug']], $plan);
        }
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

        $plan = SubscriptionPlan::where('slug', 'professional')->first();

        Subscription::updateOrCreate(
            ['office_id' => $office->id, 'status' => 'active'],
            [
                'subscription_plan_id' => $plan->id,
                'starts_at' => now(),
                'ends_at' => now()->addMonth(),
                'auto_renew' => true,
            ]
        );

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

        $this->call(VirtualTourSeeder::class);
    }
}
