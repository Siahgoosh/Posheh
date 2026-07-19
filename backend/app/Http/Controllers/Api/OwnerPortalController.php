<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Owner;
use App\Models\Property;
use Illuminate\Http\JsonResponse;

class OwnerPortalController extends Controller
{
    public function show(string $token): JsonResponse
    {
        $owner = Owner::with('office')->where('portal_token', $token)->firstOrFail();

        $properties = Property::where('owner_id', $owner->id)
            ->where('office_id', $owner->office_id)
            ->latest()
            ->get(['id', 'code', 'type', 'deal_type', 'status', 'price', 'area', 'city', 'district', 'address', 'updated_at']);

        return response()->json([
            'data' => [
                'owner' => [
                    'name' => $owner->name,
                    'mobile' => $owner->mobile ? substr($owner->mobile, 0, 4).'***'.substr($owner->mobile, -3) : null,
                ],
                'office' => [
                    'name' => $owner->office?->name,
                ],
                'properties' => $properties,
            ],
        ]);
    }
}
