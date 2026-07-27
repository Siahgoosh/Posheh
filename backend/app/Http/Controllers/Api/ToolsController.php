<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\RentalContract;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ToolsController extends Controller
{
    public function generateAdCopy(Request $request): JsonResponse
    {
        $data = $request->validate([
            'property_id' => ['required', 'integer'],
            'platform' => ['required', 'in:divar,sheypoor,instagram,telegram,whatsapp'],
        ]);

        $property = Property::where('office_id', $request->user()->office_id)->findOrFail($data['property_id']);
        $type = $property->type?->value ?? $property->type;
        $price = number_format($property->price ?? 0);
        $area = $property->area;
        $city = $property->city ?? '';
        $district = $property->district ?? '';

        $base = "🏠 {$type} | {$property->code}\n📍 {$city} {$district}\n📐 {$area} متر\n💰 {$price} تومان";

        $templates = [
            'divar' => "{$base}\n\n✅ پارکینگ · آسانسور · انباری\n📞 تماس برای بازدید\n#فروش_ملک #اجاره_ملک",
            'sheypoor' => "ملک {$type} در {$district}\nمتراژ: {$area}\nقیمت: {$price}\nکد: {$property->code}",
            'instagram' => "✨ {$type} ویژه در {$city}\n\n{$base}\n\n📩 دایرکت یا واتساپ\n#املاک #فایلینگ_املاک #{$city}",
            'telegram' => "🔹 {$property->code}\n{$base}\n\nبرای بازدید و تور مجازی پیام دهید.",
            'whatsapp' => "سلام، درباره ملک {$property->code}:\n{$base}\n\nآیا امکان بازدید هست؟",
        ];

        return response()->json([
            'data' => [
                'platform' => $data['platform'],
                'text' => $templates[$data['platform']],
            ],
        ]);
    }

    public function compareProperties(Request $request): JsonResponse
    {
        $ids = $request->validate([
            'ids' => ['required', 'array', 'min:2', 'max:4'],
            'ids.*' => ['integer'],
        ])['ids'];

        $properties = Property::where('office_id', $request->user()->office_id)
            ->whereIn('id', $ids)
            ->with('media')
            ->get();

        return response()->json(['data' => $properties]);
    }

    public function rentalContracts(Request $request): JsonResponse
    {
        $contracts = RentalContract::where('office_id', $request->user()->office_id)
            ->with('property:id,code,city,district')
            ->orderBy('end_date')
            ->paginate(20);

        return response()->json(['data' => $contracts]);
    }

    public function storeRentalContract(Request $request): JsonResponse
    {
        $data = $request->validate([
            'property_id' => ['required', 'integer'],
            'tenant_name' => ['nullable', 'string'],
            'tenant_mobile' => ['nullable', 'string'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'deposit' => ['nullable', 'integer'],
            'rent' => ['nullable', 'integer'],
            'notes' => ['nullable', 'string'],
        ]);

        $contract = RentalContract::create([
            ...$data,
            'office_id' => $request->user()->office_id,
        ]);

        return response()->json(['data' => $contract->load('property'), 'message' => 'قرارداد اجاره ثبت شد.'], 201);
    }

    public function expiringRentals(Request $request): JsonResponse
    {
        $contracts = RentalContract::where('office_id', $request->user()->office_id)
            ->where('status', 'active')
            ->whereBetween('end_date', [now(), now()->addDays(30)])
            ->with('property:id,code,city')
            ->get();

        return response()->json(['data' => $contracts]);
    }
}
