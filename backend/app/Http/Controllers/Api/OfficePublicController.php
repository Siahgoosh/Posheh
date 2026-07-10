<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Office;
use App\Models\Property;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OfficePublicController extends Controller
{
    public function show(string $slug): JsonResponse
    {
        $office = Office::where('slug', $slug)
            ->where('show_on_website', true)
            ->where('is_active', true)
            ->with('plan')
            ->firstOrFail();

        $properties = Property::where('office_id', $office->id)
            ->where('status', 'active')
            ->where('permission', 'office')
            ->latest()
            ->limit(12)
            ->get(['id', 'code', 'type', 'price', 'rent', 'deposit', 'area', 'rooms', 'city', 'district', 'description']);

        return response()->json([
            'data' => [
                'id' => $office->id,
                'name' => $office->name,
                'slug' => $office->slug,
                'city' => $office->city,
                'address' => $office->address,
                'phone' => $office->phone,
                'description' => $office->description,
                'logo_url' => $office->logo_path ? url('storage/'.$office->logo_path) : null,
                'is_verified' => $office->is_verified,
                'plan_name' => $office->plan?->name,
                'properties' => $properties,
            ],
        ]);
    }
}
