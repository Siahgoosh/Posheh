<?php

namespace App\Http\Requests\Property;

use App\Enums\PropertyCategory;
use App\Enums\PropertyPermission;
use App\Enums\PropertyStatus;
use App\Enums\PropertyType;
use App\Services\Filing\FilingSchemaRegistry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePropertyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $base = [
            'code' => ['required', 'string', 'max:50'],
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', Rule::enum(PropertyType::class)],
            'property_category' => ['required', 'string', Rule::enum(PropertyCategory::class)],
            'permission' => ['nullable', 'string', Rule::enum(PropertyPermission::class)],
            'status' => ['nullable', 'string', Rule::enum(PropertyStatus::class)],
            'owner_name' => ['nullable', 'string', 'max:255'],
            'owner_mobile' => ['required', 'string', 'max:20'],
            'contact_phone_2' => ['nullable', 'string', 'max:20'],
            'price' => ['nullable', 'integer', 'min:0'],
            'deposit' => ['nullable', 'integer', 'min:0'],
            'rent' => ['nullable', 'integer', 'min:0'],
            'area' => ['nullable', 'numeric', 'min:0'],
            'rooms' => ['nullable', 'integer', 'min:0'],
            'building_age' => ['nullable', 'integer', 'min:0'],
            'floor' => ['nullable', 'integer'],
            'total_floors' => ['nullable', 'integer', 'min:0'],
            'has_parking' => ['nullable', 'boolean'],
            'has_elevator' => ['nullable', 'boolean'],
            'has_storage' => ['nullable', 'boolean'],
            'province' => ['required', 'string', 'max:100'],
            'city' => ['required', 'string', 'max:100'],
            'district' => ['nullable', 'string', 'max:100'],
            'neighborhood' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string', 'max:5000'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'features' => ['nullable', 'array'],
            'features.*' => ['string', 'max:50'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:50'],
            'filing_data' => ['nullable', 'array'],
            'document_status' => ['nullable', 'string', 'max:50'],
            'expires_at' => ['nullable', 'date'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'show_on_website' => ['nullable', 'boolean'],
        ];

        $category = $this->input('property_category');
        $type = $this->input('type');
        if ($category && $type) {
            $dynamic = app(FilingSchemaRegistry::class)->validationRules($category, $type);
            $base = array_merge($base, $dynamic);
        }

        return $base;
    }

    public function messages(): array
    {
        return [
            'code.required' => 'کد فایل الزامی است.',
            'title.required' => 'عنوان فایل الزامی است.',
            'type.required' => 'نوع معامله را انتخاب کنید.',
            'property_category.required' => 'نوع ملک را انتخاب کنید.',
            'owner_mobile.required' => 'شماره موبایل مالک الزامی است.',
            'province.required' => 'استان الزامی است.',
            'city.required' => 'شهر الزامی است.',
            'price.required' => 'قیمت الزامی است.',
            'deposit.required' => 'مبلغ رهن الزامی است.',
            'rent.required' => 'مبلغ اجاره الزامی است.',
            'expires_at.date' => 'تاریخ انقضا نامعتبر است.',
            'assigned_to.exists' => 'مشاور انتخاب‌شده معتبر نیست.',
        ];
    }
}
