<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Office;
use Illuminate\Http\JsonResponse;

class ConsultantDirectoryController extends Controller
{
    public function index(): JsonResponse
    {
        $offices = Office::query()
            ->where('show_on_website', true)
            ->where('is_active', true)
            ->with('plan')
            ->orderByDesc('is_verified')
            ->orderBy('name')
            ->limit(24)
            ->get()
            ->map(fn (Office $office) => [
                'id' => $office->id,
                'name' => $office->name,
                'slug' => $office->slug,
                'city' => $office->city,
                'address' => $office->address,
                'phone' => $office->phone,
                'description' => $office->description,
                'logo_url' => $office->logo_path ? url('storage/'.$office->logo_path) : null,
                'is_verified' => $office->is_verified,
                'panel_type' => $office->panel_type,
                'plan_name' => $office->plan?->name,
            ]);

        return response()->json(['data' => $offices]);
    }
}
