<?php

namespace Database\Seeders;

use App\Models\Communication\CommPermission;
use App\Models\Communication\CommPipeline;
use App\Models\Communication\CommPipelineStage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CommunicationSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            ['slug' => 'comm.dashboard.view', 'name' => 'مشاهده داشبورد ارتباطات', 'group' => 'communication'],
            ['slug' => 'comm.inbox.view', 'name' => 'مشاهده اینباکس', 'group' => 'communication'],
            ['slug' => 'comm.messages.send', 'name' => 'ارسال پیام', 'group' => 'communication'],
            ['slug' => 'comm.leads.manage', 'name' => 'مدیریت سرنخ‌ها', 'group' => 'communication'],
            ['slug' => 'comm.visitors.live', 'name' => 'مشاهده بازدیدکنندگان آنلاین', 'group' => 'communication'],
            ['slug' => 'comm.settings.manage', 'name' => 'تنظیمات ارتباطات', 'group' => 'communication'],
        ];

        foreach ($permissions as $p) {
            CommPermission::firstOrCreate(['slug' => $p['slug']], $p);
        }

        $allPermissionIds = CommPermission::pluck('id', 'slug');

        $roleMap = [
            'super_admin' => array_keys($allPermissionIds->all()),
            'platform_admin' => array_keys($allPermissionIds->all()),
            'platform_support' => ['comm.dashboard.view', 'comm.inbox.view', 'comm.messages.send', 'comm.leads.manage', 'comm.visitors.live'],
            'platform_finance' => ['comm.dashboard.view', 'comm.inbox.view', 'comm.visitors.live'],
        ];

        foreach ($roleMap as $role => $slugs) {
            foreach ($slugs as $slug) {
                $permId = $allPermissionIds[$slug] ?? null;
                if (! $permId) {
                    continue;
                }
                DB::table('comm_role_permissions')->updateOrInsert(
                    ['role' => $role, 'permission_id' => $permId],
                    ['created_at' => now(), 'updated_at' => now()],
                );
            }
        }

        $pipeline = CommPipeline::firstOrCreate(
            ['name' => 'پوشه — جذب مشتری'],
            ['is_default' => true, 'sort_order' => 0],
        );

        $stages = [
            ['name' => 'سرنخ جدید', 'slug' => 'new', 'color' => '#22d3ee', 'sort_order' => 0],
            ['name' => 'تماس', 'slug' => 'contacted', 'color' => '#a78bfa', 'sort_order' => 1],
            ['name' => 'واجد شرایط', 'slug' => 'qualified', 'color' => '#4ade80', 'sort_order' => 2],
            ['name' => 'دمو', 'slug' => 'demo', 'color' => '#fbbf24', 'sort_order' => 3],
            ['name' => 'موفق', 'slug' => 'won', 'color' => '#22c55e', 'sort_order' => 4, 'is_won' => true],
            ['name' => 'از دست رفت', 'slug' => 'lost', 'color' => '#fb7185', 'sort_order' => 5, 'is_lost' => true],
        ];

        foreach ($stages as $stage) {
            CommPipelineStage::updateOrCreate(
                ['pipeline_id' => $pipeline->id, 'slug' => $stage['slug']],
                array_merge($stage, ['pipeline_id' => $pipeline->id]),
            );
        }
    }
}
