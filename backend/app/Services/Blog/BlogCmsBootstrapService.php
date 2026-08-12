<?php

namespace App\Services\Blog;

use App\Http\Controllers\Api\Blog\BlogController;
use App\Models\BlogAuthor;
use App\Models\BlogCategory;
use Illuminate\Support\Facades\Schema;

class BlogCmsBootstrapService
{
    /** @return array{ok: bool, message_fa: string, categories?: int, authors?: int} */
    public function ensureDefaults(): array
    {
        if (! Schema::hasTable('blog_categories') || ! Schema::hasTable('blog_authors')) {
            return [
                'ok' => false,
                'message_fa' => 'جداول وبلاگ موجود نیست — ابتدا migrate را اجرا کنید.',
            ];
        }

        try {
            foreach (BlogController::CATEGORIES as $slug => $label) {
                BlogCategory::updateOrCreate(
                    ['slug' => $slug],
                    [
                        'name' => $label,
                        'description' => "مقالات تخصصی {$label} برای مشاوران و دفاتر املاک.",
                        'seo_title' => "{$label} | وبلاگ پوشه",
                        'meta_description' => "راهنما و مقالات {$label} — مرجع فارسی نرم‌افزار و مدیریت املاک.",
                        'is_active' => true,
                        'is_indexable' => true,
                        'sort_order' => 0,
                    ]
                );
            }

            BlogAuthor::updateOrCreate(
                ['slug' => 'posheh-content-team'],
                [
                    'name' => 'تیم محتوای پوشه',
                    'bio' => 'تیم محتوای پوشه مقالات کاربردی مدیریت دفتر املاک، CRM و فایلینگ را برای مشاوران ایران می‌نویسد.',
                    'role' => 'Editorial',
                    'is_active' => true,
                    'is_indexable' => true,
                ]
            );

            return [
                'ok' => true,
                'message_fa' => 'دسته‌ها و نویسنده پیش‌فرض وبلاگ آماده‌اند.',
                'categories' => BlogCategory::count(),
                'authors' => BlogAuthor::count(),
            ];
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'message_fa' => 'خطا در راه‌اندازی وبلاگ: '.$e->getMessage(),
            ];
        }
    }
}
