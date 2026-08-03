<?php

namespace App\Http\Controllers\Api\Property;

use App\Http\Controllers\Controller;
use App\Http\Resources\PublicPropertyResource;
use App\Models\Property;
use Illuminate\Http\JsonResponse;

class PropertyPublicController extends Controller
{
    public function byQr(string $token): JsonResponse
    {
        $property = Property::where('qr_token', $token)
            ->with(['media', 'office:id,name,slug,brand_name'])
            ->firstOrFail();

        return response()->json([
            'data' => new PublicPropertyResource($property),
            'office' => [
                'name' => $property->office->brand_name ?? $property->office->name,
                'slug' => $property->office->slug,
            ],
        ]);
    }
}
