<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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
            ->where('status', 'active')
            ->paginate(min((int) $request->input('per_page', 20), 50));

        return response()->json($properties);
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
