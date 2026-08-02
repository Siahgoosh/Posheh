<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class PlatformUsersExport implements FromCollection, WithHeadings, WithTitle
{
    /** @param Collection<int, array<string, mixed>> $rows */
    public function __construct(private readonly Collection $rows) {}

    public function collection(): Collection
    {
        return $this->rows->map(fn (array $row) => [
            $row['id'],
            $row['name'],
            $row['mobile'],
            $row['email'] ?? '',
            $row['username'] ?? '',
            $row['role_label'] ?? '',
            $row['office_name'] ?? '',
            $row['platform_label'] ?? '',
            $row['app_version'] ?? '',
            $row['device_name'] ?? '',
            ($row['is_active'] ?? false) ? 'فعال' : 'غیرفعال',
            ($row['account_active'] ?? false) ? 'بله' : 'خیر',
            $row['last_active_at'] ?? '',
            $row['created_at'] ?? '',
        ]);
    }

    public function headings(): array
    {
        return [
            'شناسه', 'نام', 'موبایل', 'ایمیل', 'نام کاربری', 'نقش', 'دفتر',
            'پلتفرم', 'نسخه اپ', 'دستگاه', 'وضعیت فعالیت', 'حساب فعال',
            'آخرین فعالیت', 'تاریخ ثبت‌نام',
        ];
    }

    public function title(): string
    {
        return 'کاربران پلتفرم';
    }
}
