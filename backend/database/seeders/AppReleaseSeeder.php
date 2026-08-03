<?php

namespace Database\Seeders;

use App\Models\AppRelease;
use Illuminate\Database\Seeder;

class AppReleaseSeeder extends Seeder
{
    public function run(): void
    {
        $releases = [
            [
                'platform' => 'android',
                'version' => '1.1.0',
                'title' => 'اپلیکیشن اندروید پوشه',
                'description' => 'فایلینگ کامل، CRM، کیف پول، دستیار AI — سازگار با کافه‌بازار (Poolakey)، ۴۸ ساعت رایگان پنل فردی.',
                'download_url' => '/downloads/posheh-android.apk',
                'file_size' => '~۵۵ مگابایت',
                'is_published' => true,
                'published_at' => now(),
            ],
            [
                'platform' => 'windows',
                'version' => '1.1.0',
                'title' => 'نسخه دسکتاپ پوشه',
                'description' => 'نسخه ویندوز Flutter — فایلینگ، CRM، گزارش و همگام‌سازی ابری.',
                'download_url' => '/downloads/posheh-windows.zip',
                'file_size' => 'سبک',
                'is_published' => true,
                'published_at' => now(),
            ],
            [
                'platform' => 'pwa',
                'version' => '1.1.0',
                'title' => 'نسخه PWA (وب‌اپ)',
                'description' => 'نصب پوشه روی موبایل یا دسکتاپ از مرورگر — تم جدید، فایلینگ کامل، بدون نیاز به استور.',
                'download_url' => '/',
                'file_size' => 'سبک',
                'is_published' => true,
                'published_at' => now(),
            ],
        ];

        foreach ($releases as $release) {
            AppRelease::updateOrCreate(
                ['platform' => $release['platform'], 'version' => $release['version']],
                $release
            );
        }
    }
}
