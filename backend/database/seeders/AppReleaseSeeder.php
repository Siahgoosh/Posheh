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
                'version' => '1.0.1',
                'title' => 'اپلیکیشن اندروید پوشه',
                'description' => 'فایلینگ، CRM، حسابداری و گزارش برای مشاوران املاک — ورود OTP، همگام با وب، ۴۸ ساعت رایگان پنل فردی.',
                'download_url' => '/downloads/posheh-android.apk',
                'file_size' => '~۵۵ مگابایت',
                'is_published' => true,
                'published_at' => now(),
            ],
            [
                'platform' => 'windows',
                'version' => '1.0.1',
                'title' => 'نسخه ویندوز پوشه',
                'description' => 'نرم‌افزار دسکتاپ Flutter برای دفتر املاک — همان امکانات اندروید روی ویندوز ۱۰/۱۱.',
                'download_url' => '/downloads/posheh-windows.zip',
                'file_size' => '~۴۵ مگابایت',
                'is_published' => true,
                'published_at' => now(),
            ],
            [
                'platform' => 'pwa',
                'version' => '1.0.1',
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
