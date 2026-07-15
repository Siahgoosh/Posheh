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
                'version' => '1.0.0',
                'title' => 'اپلیکیشن اندروید پوشه',
                'description' => 'نسخه اندروید برای مشاوران و مدیران دفتر املاک — ورود با OTP و مدیریت فایلینگ.',
                'download_url' => '/downloads/posheh-android.apk',
                'file_size' => '~۱۵ مگابایت',
                'is_published' => true,
                'published_at' => now(),
            ],
            [
                'platform' => 'windows',
                'version' => '1.0.0',
                'title' => 'نسخه ویندوز پوشه',
                'description' => 'نرم‌افزار دسکتاپ ویندوز (PWA) برای استفاده در دفتر املاک.',
                'download_url' => '/downloads/posheh-windows.zip',
                'file_size' => '~۱ مگابایت',
                'is_published' => true,
                'published_at' => now(),
            ],
            [
                'platform' => 'pwa',
                'version' => '1.0.0',
                'title' => 'نسخه PWA (وب‌اپ)',
                'description' => 'نصب پوشه روی موبایل یا دسکتاپ از مرورگر — بدون نیاز به استور.',
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
