<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Property;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PropertyCompareController extends Controller
{
    public function compare(Request $request): JsonResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:2', 'max:4'],
            'ids.*' => ['integer'],
        ]);

        $properties = Property::where('office_id', $request->user()->office_id)
            ->whereIn('id', $data['ids'])
            ->with(['owner', 'media'])
            ->get();

        if ($properties->count() < 2) {
            return response()->json(['message' => 'حداقل دو ملک معتبر انتخاب کنید.'], 422);
        }

        $fields = ['code', 'type', 'deal_type', 'status', 'price', 'area', 'rooms', 'floor', 'city', 'district', 'address', 'year_built', 'parking', 'elevator', 'warehouse'];

        return response()->json([
            'data' => [
                'properties' => $properties,
                'fields' => $fields,
            ],
        ]);
    }
}
