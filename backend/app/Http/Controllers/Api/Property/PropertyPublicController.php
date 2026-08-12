<?php

namespace App\Http\Controllers\Api\Property;

use App\Enums\PropertyStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\PublicPropertyResource;
use App\Models\Property;
use Illuminate\Http\JsonResponse;

class PropertyPublicController extends Controller
{
    public function byQr(string $token): JsonResponse
    {
        $property = Property::where('qr_token', $token)
            ->where('status', PropertyStatus::Active)
            ->with(['media', 'office:id,name,slug,settings'])
            ->firstOrFail();

        $office = $property->office;
        $brandName = data_get($office?->settings, 'brand_name')
            ?: data_get($office?->settings, 'brandName')
            ?: $office?->name;

        return response()->json([
            'data' => new PublicPropertyResource($property),
            'office' => [
                'name' => $brandName,
                'slug' => $office?->slug,
            ],
        ]);
    }
}
