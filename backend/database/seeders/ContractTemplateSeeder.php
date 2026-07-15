<?php

namespace Database\Seeders;

use App\Models\ContractTemplate;
use Illuminate\Database\Seeder;

class ContractTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $mubayaehBody = file_exists(resource_path('contracts/mubayaeh-125.html'))
            ? file_get_contents(resource_path('contracts/mubayaeh-125.html'))
            : '<h2>مبایعه‌نامه</h2><p>فروشنده: {{seller_name}} — خریدار: {{buyer_name}}</p>';

        $templates = [
            [
                'slug' => 'mubayaeh-125',
                'name' => 'مبایعه‌نامه فرم ۱۲۵ (اتحادیه)',
                'type' => 'sale',
                'body' => $mubayaehBody,
            ],
            [
                'slug' => 'sale-contract',
                'name' => 'قرارداد مبایعه‌نامه ساده',
                'type' => 'sale',
                'body' => '<h2>قرارداد مبایعه‌نامه</h2>
<p>این قرارداد فی‌مابین <strong>{{seller_name}}</strong> (فروشنده) و <strong>{{buyer_name}}</strong> (خریدار) منعقد می‌گردد.</p>
<p>موضوع: انتقال ملک به کد <strong>{{property_code}}</strong> واقع در <strong>{{property_address}}</strong></p>
<p>مبلغ توافقی: <strong>{{property_price}}</strong> تومان</p>
<p>تاریخ: {{contract_date}}</p>
<p>دفتر: {{office_name}}</p>',
            ],
            [
                'slug' => 'rent-contract',
                'name' => 'قرارداد اجاره‌نامه',
                'type' => 'rent',
                'body' => '<h2>قرارداد اجاره‌نامه</h2>
<p>موجر: <strong>{{seller_name}}</strong> — مستأجر: <strong>{{buyer_name}}</strong></p>
<p>ملک: {{property_code}} — {{property_address}}</p>
<p>تاریخ: {{contract_date}} — دفتر: {{office_name}}</p>',
            ],
        ];

        foreach ($templates as $t) {
            ContractTemplate::updateOrCreate(['slug' => $t['slug']], $t);
        }
    }
}
