<?php

namespace App\Http\Controllers\Api;

use App\Enums\PropertyStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\PublicPropertyResource;
use App\Models\Office;
use App\Models\OfficeApiKey;
use App\Models\Property;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicApiController extends Controller
{
    public function properties(Request $request): JsonResponse
    {
        $office = $this->resolveOffice($request);
        $properties = Property::where('office_id', $office->id)
            ->where('status', PropertyStatus::Active)
            ->where('show_on_website', true)
            ->where('website_approved', true)
            ->with('media')
            ->latest()
            ->paginate(min((int) $request->input('per_page', 20), 50));

        return response()->json([
            'data' => PublicPropertyResource::collection($properties),
            'meta' => [
                'current_page' => $properties->currentPage(),
                'last_page' => $properties->lastPage(),
                'per_page' => $properties->perPage(),
                'total' => $properties->total(),
            ],
        ]);
    }

    private function resolveOffice(Request $request): Office
    {
        $key = $request->header('X-Api-Key') ?? $request->query('api_key');
        abort_unless($key, 401, 'API key required');

        $record = OfficeApiKey::query()
            ->where('key_hash', hash('sha256', $key))
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->firstOrFail();

        $record->update(['last_used_at' => now()]);

        return Office::findOrFail($record->office_id);
    }
}
