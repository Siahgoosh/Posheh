<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PropertiesImportTemplateExport implements FromArray, WithHeadings, WithStyles
{
    public function headings(): array
    {
        return [
            'code',
            'type',
            'status',
            'price',
            'deposit',
            'rent',
            'area',
            'rooms',
            'city',
            'district',
            'address',
            'owner_name',
            'owner_mobile',
            'description',
        ];
    }

    public function array(): array
    {
        return [
            [
                'P-1001',
                'sale',
                'active',
                8500000000,
                '',
                '',
                120,
                2,
                'لامرد',
                'مرکز',
                'خیابان امام',
                'علی رضایی',
                '09121234567',
                'واحد نوساز جنوبی',
            ],
            [
                'P-1002',
                'rent',
                'active',
                '',
                500000000,
                15000000,
                90,
                2,
                'لامرد',
                'فاز ۲',
                '',
                'مریم احمدی',
                '09129876543',
                'اجاره آپارتمان',
            ],
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
