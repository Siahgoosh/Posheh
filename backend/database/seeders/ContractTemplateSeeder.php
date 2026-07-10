<?php

namespace Database\Seeders;

use App\Models\ContractTemplate;
use Illuminate\Database\Seeder;

class ContractTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'slug' => 'sale-contract',
                'name' => 'قرارداد مبایعه‌نامه',
                'type' => 'sale',
                'body' => '<h2>قرارداد مبایعه‌نامه</h2>
<p>این قرارداد فی‌مابین <strong>{{party_a}}</strong> (فروشنده) و <strong>{{party_b}}</strong> (خریدار) منعقد می‌گردد.</p>
<p>موضوع: انتقال ملک به کد <strong>{{property_code}}</strong> واقع در <strong>{{property_address}}</strong></p>
<p>مبلغ توافقی: <strong>{{price}}</strong> ریال</p>
<p>تاریخ: {{date}}</p>
<p>دفتر: {{office_name}}</p>',
            ],
            [
                'slug' => 'rent-contract',
                'name' => 'قرارداد اجاره‌نامه',
                'type' => 'rent',
                'body' => '<h2>قرارداد اجاره‌نامه</h2>
<p>موجر: <strong>{{party_a}}</strong> — مستأجر: <strong>{{party_b}}</strong></p>
<p>ملک: {{property_code}} — {{property_address}}</p>
<p>تاریخ: {{date}} — دفتر: {{office_name}}</p>',
            ],
        ];

        foreach ($templates as $t) {
            ContractTemplate::updateOrCreate(['slug' => $t['slug']], $t);
        }
    }
}
