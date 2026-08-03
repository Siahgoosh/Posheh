<?php

namespace App\Services\Filing;

use App\Enums\PropertyCategory;
use App\Enums\PropertyPermission;
use App\Enums\PropertyStatus;
use App\Enums\PropertyType;

class FilingSchemaRegistry
{
    public function fullSchema(): array
    {
        return [
            'property_types' => $this->propertyTypes(),
            'transaction_types' => $this->transactionTypes(),
            'statuses' => $this->statuses(),
            'permissions' => $this->permissions(),
            'sections' => $this->sections(),
            'shared_fields' => $this->sharedFields(),
            'owner_fields' => $this->ownerFields(),
            'location_fields' => $this->locationFields(),
            'common_property_fields' => $this->commonPropertyFields(),
            'amenity_options' => $this->amenityOptions(),
            'document_status_options' => $this->documentStatusOptions(),
            'tag_options' => $this->tagOptions(),
            'property_type_fields' => $this->propertyTypeFieldMap(),
            'transaction_type_fields' => $this->transactionTypeFieldMap(),
        ];
    }

    /** @return array<int, array{value: string, label: string}> */
    public function propertyTypes(): array
    {
        return $this->enumList(PropertyCategory::cases());
    }

    /** @return array<int, array{value: string, label: string}> */
    public function transactionTypes(): array
    {
        return collect(PropertyType::cases())
            ->reject(fn (PropertyType $t) => $t->isLegacy())
            ->map(fn (PropertyType $t) => ['value' => $t->value, 'label' => $t->label()])
            ->values()
            ->all();
    }

    /** @return array<int, array{value: string, label: string}> */
    private function statuses(): array
    {
        return $this->enumList(PropertyStatus::cases());
    }

    /** @return array<int, array{value: string, label: string}> */
    private function permissions(): array
    {
        return $this->enumList(PropertyPermission::cases());
    }

    /** @param array<\BackedEnum> $cases */
    private function enumList(array $cases): array
    {
        return array_map(fn ($e) => [
            'value' => $e->value,
            'label' => method_exists($e, 'label') ? $e->label() : $e->value,
        ], $cases);
    }

    private function sections(): array
    {
        return [
            ['id' => 'general', 'label' => 'اطلاعات عمومی', 'icon' => 'file'],
            ['id' => 'owner', 'label' => 'مالک', 'icon' => 'user'],
            ['id' => 'location', 'label' => 'موقعیت مکانی', 'icon' => 'map-pin'],
            ['id' => 'property', 'label' => 'مشخصات ملک', 'icon' => 'home'],
            ['id' => 'transaction', 'label' => 'معامله', 'icon' => 'banknote'],
            ['id' => 'amenities', 'label' => 'امکانات', 'icon' => 'sparkles'],
            ['id' => 'documents', 'label' => 'سند و مدارک', 'icon' => 'file-text'],
            ['id' => 'media', 'label' => 'تصاویر', 'icon' => 'image'],
            ['id' => 'notes', 'label' => 'توضیحات و برچسب', 'icon' => 'tag'],
        ];
    }

    private function sharedFields(): array
    {
        return [
            $this->field('code', 'کد فایل', 'text', ['section' => 'general', 'required' => true, 'hint' => 'یکتا در دفتر']),
            $this->field('title', 'عنوان فایل', 'text', ['section' => 'general', 'required' => true]),
            $this->field('type', 'نوع معامله', 'select', ['section' => 'general', 'required' => true, 'options' => $this->transactionTypes()]),
            $this->field('property_category', 'نوع ملک', 'select', ['section' => 'general', 'required' => true, 'options' => $this->propertyTypes()]),
            $this->field('status', 'وضعیت فایل', 'select', ['section' => 'general', 'options' => $this->statuses(), 'default' => 'active']),
            $this->field('permission', 'سطح دسترسی', 'select', ['section' => 'general', 'options' => $this->permissions(), 'default' => 'office']),
            $this->field('assigned_to', 'مشاور مسئول', 'user_select', ['section' => 'general']),
            $this->field('expires_at', 'تاریخ انقضا', 'jalali_date', ['section' => 'general']),
            $this->field('show_on_website', 'نمایش در وبسایت', 'boolean', ['section' => 'general']),
        ];
    }

    private function ownerFields(): array
    {
        return [
            $this->field('owner_first_name', 'نام مالک', 'text', ['section' => 'owner', 'storage' => 'filing_data.owner']),
            $this->field('owner_last_name', 'نام خانوادگی', 'text', ['section' => 'owner', 'storage' => 'filing_data.owner']),
            // owner_name is auto-composed from first+last — internal only, never on public site
            $this->field('owner_mobile', 'موبایل', 'phone', ['section' => 'owner', 'required' => true]),
            $this->field('contact_phone_2', 'شماره تماس دوم', 'phone', ['section' => 'owner']),
            $this->field('owner_landline', 'تلفن ثابت', 'phone', ['section' => 'owner', 'storage' => 'filing_data.owner']),
            $this->field('owner_national_id', 'کد ملی', 'text', ['section' => 'owner', 'storage' => 'filing_data.owner']),
            $this->field('owner_address', 'آدرس مالک', 'textarea', ['section' => 'owner', 'storage' => 'filing_data.owner']),
            $this->field('owner_type', 'نوع مالک', 'select', [
                'section' => 'owner',
                'storage' => 'filing_data.owner',
                'options' => [
                    ['value' => 'owner', 'label' => 'مالک اصلی'],
                    ['value' => 'attorney', 'label' => 'وکیل'],
                    ['value' => 'heir', 'label' => 'ورثه'],
                ],
            ]),
            $this->field('owner_notes', 'توضیحات مالک', 'textarea', ['section' => 'owner', 'storage' => 'filing_data.owner']),
        ];
    }

    private function locationFields(): array
    {
        return [
            $this->field('province', 'استان', 'province_select', ['section' => 'location', 'required' => true]),
            $this->field('city', 'شهر', 'text', ['section' => 'location', 'required' => true]),
            $this->field('district', 'منطقه', 'text', ['section' => 'location']),
            $this->field('neighborhood', 'محله', 'text', ['section' => 'location']),
            $this->field('street', 'خیابان', 'text', ['section' => 'location', 'storage' => 'filing_data.location']),
            $this->field('alley', 'کوچه', 'text', ['section' => 'location', 'storage' => 'filing_data.location']),
            $this->field('plaque', 'پلاک', 'text', ['section' => 'location', 'storage' => 'filing_data.location']),
            $this->field('registration_plaque', 'پلاک ثبتی', 'text', ['section' => 'location', 'storage' => 'filing_data.location']),
            $this->field('address', 'آدرس کامل', 'textarea', ['section' => 'location']),
            $this->field('latitude', 'عرض جغرافیایی', 'number', ['section' => 'location']),
            $this->field('longitude', 'طول جغرافیایی', 'number', ['section' => 'location']),
        ];
    }

    private function commonPropertyFields(): array
    {
        return [
            $this->field('land_area', 'متراژ زمین', 'number', ['section' => 'property', 'unit' => 'متر', 'storage' => 'filing_data.specs']),
            $this->field('area', 'متراژ بنا', 'number', ['section' => 'property', 'unit' => 'متر']),
            $this->field('width', 'عرض', 'number', ['section' => 'property', 'unit' => 'متر', 'storage' => 'filing_data.specs']),
            $this->field('length', 'طول', 'number', ['section' => 'property', 'unit' => 'متر', 'storage' => 'filing_data.specs']),
            $this->field('frontage', 'بر', 'number', ['section' => 'property', 'unit' => 'متر', 'storage' => 'filing_data.specs']),
            $this->field('orientation', 'جهت', 'select', [
                'section' => 'property',
                'storage' => 'filing_data.specs',
                'options' => $this->options(['شمالی', 'جنوبی', 'شرقی', 'غربی', 'دو نبش', 'سه نبش', 'چهار نبش']),
            ]),
            $this->field('building_age', 'سال ساخت', 'number', ['section' => 'property']),
            $this->field('total_floors', 'تعداد طبقات', 'number', ['section' => 'property']),
            $this->field('floor', 'طبقه', 'number', ['section' => 'property']),
            $this->field('units_count', 'تعداد واحد', 'number', ['section' => 'property', 'storage' => 'filing_data.specs']),
            $this->field('rooms', 'تعداد خواب', 'number', ['section' => 'property']),
            $this->field('has_parking', 'پارکینگ', 'boolean', ['section' => 'property']),
            $this->field('has_storage', 'انباری', 'boolean', ['section' => 'property']),
            $this->field('has_elevator', 'آسانسور', 'boolean', ['section' => 'property']),
            $this->field('has_balcony', 'بالکن', 'boolean', ['section' => 'property', 'storage' => 'filing_data.specs']),
            $this->field('has_terrace', 'تراس', 'boolean', ['section' => 'property', 'storage' => 'filing_data.specs']),
            $this->field('has_yard', 'حیاط', 'boolean', ['section' => 'property', 'storage' => 'filing_data.specs']),
        ];
    }

    private function propertyTypeFieldMap(): array
    {
        $land = [
            $this->field('land_use', 'نوع زمین', 'select', ['options' => $this->options(['مسکونی', 'تجاری', 'کشاورزی', 'صنعتی', 'باغ', 'باغ ویلا'])]),
            $this->field('in_plan', 'داخل طرح', 'boolean'),
            $this->field('density', 'تراکم', 'text'),
            $this->field('allowed_floors', 'طبقات مجاز', 'number'),
            $this->field('slope', 'شیب', 'text'),
            $this->field('soil_type', 'نوع خاک', 'text'),
            $this->field('well', 'چاه', 'boolean'),
            $this->field('qanat', 'قنات', 'boolean'),
            $this->field('fence', 'حصار', 'boolean'),
            $this->field('trees', 'درخت', 'boolean'),
        ];

        $apartment = [
            $this->field('unit_number', 'شماره واحد', 'text'),
            $this->field('renovated', 'بازسازی شده', 'boolean'),
            $this->field('master_bedroom', 'خواب مستر', 'boolean'),
            $this->field('lobby', 'لابی', 'boolean'),
            $this->field('guard', 'نگهبانی', 'boolean'),
            $this->field('janitor', 'سرایدار', 'boolean'),
        ];

        $villa = [
            $this->field('duplex', 'دوبلکس', 'boolean'),
            $this->field('triplex', 'تریپلکس', 'boolean'),
            $this->field('pool', 'استخر', 'boolean'),
            $this->field('jacuzzi', 'جکوزی', 'boolean'),
            $this->field('roof_garden', 'روف گاردن', 'boolean'),
            $this->field('gazebo', 'آلاچیق', 'boolean'),
            $this->field('bbq', 'باربیکیو', 'boolean'),
            $this->field('smart_home', 'سیستم هوشمند', 'boolean'),
        ];

        $oldHouse = [
            $this->field('demolishable', 'قابل تخریب', 'boolean'),
        ];

        $shop = [
            $this->field('shop_front_width', 'عرض ویترین', 'number', ['unit' => 'متر']),
            $this->field('ceiling_height', 'ارتفاع', 'number', ['unit' => 'متر']),
            $this->field('shop_use', 'کاربری', 'text'),
            $this->field('three_phase_power', 'برق سه فاز', 'boolean'),
        ];

        $garden = [
            $this->field('tree_count', 'تعداد درخت', 'number'),
            $this->field('tree_type', 'نوع درخت', 'text'),
            $this->field('tree_age', 'سن درخت', 'text'),
            $this->field('water_share', 'سهم آب', 'text'),
            $this->field('garden_house', 'خانه باغ', 'boolean'),
        ];

        return [
            PropertyCategory::Land->value => $this->withStorage($land, 'filing_data.specs'),
            PropertyCategory::Apartment->value => $this->withStorage($apartment, 'filing_data.specs'),
            PropertyCategory::Villa->value => $this->withStorage($villa, 'filing_data.specs'),
            PropertyCategory::OldHouse->value => $this->withStorage($oldHouse, 'filing_data.specs'),
            PropertyCategory::Shop->value => $this->withStorage($shop, 'filing_data.specs'),
            PropertyCategory::Garden->value => $this->withStorage($garden, 'filing_data.specs'),
            PropertyCategory::GardenVilla->value => $this->withStorage(array_merge($villa, $garden), 'filing_data.specs'),
            PropertyCategory::Office->value => $this->withStorage($apartment, 'filing_data.specs'),
            PropertyCategory::CommercialUnit->value => $this->withStorage($shop, 'filing_data.specs'),
            PropertyCategory::Warehouse->value => $this->withStorage($land, 'filing_data.specs'),
            PropertyCategory::Factory->value => $this->withStorage($land, 'filing_data.specs'),
            PropertyCategory::AgriculturalLand->value => $this->withStorage($land, 'filing_data.specs'),
            PropertyCategory::Greenhouse->value => $this->withStorage($garden, 'filing_data.specs'),
            PropertyCategory::Livestock->value => $this->withStorage($land, 'filing_data.specs'),
            PropertyCategory::Storage->value => $this->withStorage($land, 'filing_data.specs'),
            PropertyCategory::ConstructionProject->value => $this->withStorage($land, 'filing_data.specs'),
            PropertyCategory::PreSaleUnit->value => $this->withStorage($apartment, 'filing_data.specs'),
            PropertyCategory::ResidentialComplex->value => $this->withStorage($apartment, 'filing_data.specs'),
            PropertyCategory::CommercialComplex->value => $this->withStorage($shop, 'filing_data.specs'),
            PropertyCategory::Hotel->value => $this->withStorage($villa, 'filing_data.specs'),
            PropertyCategory::Parking->value => $this->withStorage([], 'filing_data.specs'),
            PropertyCategory::Suite->value => $this->withStorage($apartment, 'filing_data.specs'),
            PropertyCategory::Townhouse->value => $this->withStorage($villa, 'filing_data.specs'),
            PropertyCategory::Other->value => [],
        ];
    }

    private function transactionTypeFieldMap(): array
    {
        $sale = [
            $this->field('price', 'قیمت کل', 'currency', ['required' => true]),
            $this->field('price_per_meter', 'قیمت هر متر', 'currency', ['storage' => 'filing_data.transaction']),
            $this->field('negotiable', 'قابل مذاکره', 'boolean', ['storage' => 'filing_data.transaction']),
            $this->field('payment_type', 'نوع پرداخت', 'select', [
                'storage' => 'filing_data.transaction',
                'options' => $this->options(['نقد', 'اقساط', 'معاوضه']),
            ]),
        ];

        $mortgage = [
            $this->field('deposit', 'مبلغ رهن', 'currency', ['required' => true]),
            $this->field('convertible', 'قابل تبدیل', 'boolean', ['storage' => 'filing_data.transaction']),
            $this->field('min_deposit', 'حداقل رهن', 'currency', ['storage' => 'filing_data.transaction']),
            $this->field('max_deposit', 'حداکثر رهن', 'currency', ['storage' => 'filing_data.transaction']),
        ];

        $rent = [
            $this->field('rent', 'مبلغ اجاره', 'currency', ['required' => true]),
            $this->field('deposit', 'ودیعه', 'currency'),
            $this->field('convertible', 'قابل تبدیل', 'boolean', ['storage' => 'filing_data.transaction']),
            $this->field('contract_duration', 'مدت قرارداد', 'text', ['storage' => 'filing_data.transaction']),
        ];

        $mortgageRent = array_merge($mortgage, $rent);

        $partnership = [
            $this->field('owner_share_percent', 'درصد مالک', 'number', ['storage' => 'filing_data.transaction']),
            $this->field('builder_share_percent', 'درصد سازنده', 'number', ['storage' => 'filing_data.transaction']),
            $this->field('partnership_floors', 'تعداد طبقات', 'number', ['storage' => 'filing_data.transaction']),
            $this->field('partnership_density', 'تراکم', 'text', ['storage' => 'filing_data.transaction']),
            $this->field('owner_contribution', 'آورده مالک', 'currency', ['storage' => 'filing_data.transaction']),
            $this->field('builder_contribution', 'آورده سازنده', 'currency', ['storage' => 'filing_data.transaction']),
        ];

        return [
            PropertyType::Sale->value => $sale,
            PropertyType::FullMortgage->value => $mortgage,
            PropertyType::Rent->value => $rent,
            PropertyType::MortgageRent->value => $mortgageRent,
            PropertyType::PreSale->value => $sale,
            PropertyType::ConstructionPartnership->value => $partnership,
            PropertyType::Exchange->value => $sale,
            PropertyType::Barter->value => $sale,
            PropertyType::InstallmentSale->value => $sale,
            PropertyType::Auction->value => $sale,
        ];
    }

    public function fieldsFor(string $propertyCategory, string $transactionType): array
    {
        $schema = $this->fullSchema();
        $common = $schema['common_property_fields'];
        $specific = $schema['property_type_fields'][$propertyCategory] ?? [];
        $transaction = $schema['transaction_type_fields'][$transactionType] ?? [];

        return [
            'shared' => $schema['shared_fields'],
            'owner' => $schema['owner_fields'],
            'location' => $schema['location_fields'],
            'property' => array_merge($common, $specific),
            'transaction' => $transaction,
            'amenities' => [
                $this->field('features', 'امکانات', 'multiselect', [
                    'section' => 'amenities',
                    'options' => $schema['amenity_options'],
                ]),
            ],
            'documents' => [
                $this->field('document_status', 'وضعیت سند', 'select', [
                    'section' => 'documents',
                    'options' => $schema['document_status_options'],
                ]),
            ],
            'notes' => [
                $this->field('description', 'توضیحات', 'textarea', ['section' => 'notes']),
                $this->field('tags', 'برچسب‌ها', 'multiselect', [
                    'section' => 'notes',
                    'options' => $schema['tag_options'],
                ]),
            ],
        ];
    }

    public function validationRules(string $propertyCategory, string $transactionType): array
    {
        $rules = [
            'code' => ['required', 'string', 'max:50'],
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string'],
            'property_category' => ['required', 'string'],
            'owner_mobile' => ['required', 'string', 'max:20'],
            'province' => ['required', 'string', 'max:100'],
            'city' => ['required', 'string', 'max:100'],
        ];

        foreach ($this->fieldsFor($propertyCategory, $transactionType) as $group) {
            foreach ($group as $field) {
                if (empty($field['required'])) {
                    continue;
                }
                $key = $field['key'];
                if (isset($rules[$key])) {
                    continue;
                }
                $rules[$key] = match ($field['type']) {
                    'currency', 'number' => ['required', 'numeric', 'min:0'],
                    'boolean' => ['required', 'boolean'],
                    default => ['required', 'string', 'max:500'],
                };
            }
        }

        return $rules;
    }

    private function amenityOptions(): array
    {
        return $this->options([
            'آب', 'برق', 'گاز', 'تلفن', 'فیبر نوری', 'اینترنت',
            'سرمایش', 'گرمایش', 'کابینت', 'کفپوش', 'نما',
            'استخر', 'سونا', 'جکوزی', 'لابی', 'نگهبانی', 'دوربین', 'سرایدار',
            'بالکن', 'مبله', 'انباری', 'پارکینگ',
        ]);
    }

    private function documentStatusOptions(): array
    {
        return $this->options([
            'شش‌دانگ', 'تک‌برگ', 'دفترچه', 'قولنامه', 'وکالتی', 'اوقافی', 'مشاع', 'فاقد سند',
        ]);
    }

    private function tagOptions(): array
    {
        return $this->options([
            'فوری', 'ویژه', 'زیر قیمت', 'سرمایه‌گذاری', 'بدون واسطه', 'قابل معاوضه',
        ]);
    }

  /** @param array<string> $labels */
    private function options(array $labels): array
    {
        return array_map(fn ($l) => ['value' => $l, 'label' => $l], $labels);
    }

    private function field(string $key, string $label, string $type, array $extra = []): array
    {
        return array_merge([
            'key' => $key,
            'label' => $label,
            'type' => $type,
            'section' => 'property',
            'required' => false,
        ], $extra);
    }

    /** @param array<int, array<string, mixed>> $fields */
    private function withStorage(array $fields, string $storage): array
    {
        return array_map(function (array $f) use ($storage) {
            $f['storage'] = $f['storage'] ?? $storage;
            $f['section'] = 'property';

            return $f;
        }, $fields);
    }
}
