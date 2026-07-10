<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Office;
use App\Models\OfficeApiKey;
use App\Models\Property;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ApiKeyController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $keys = OfficeApiKey::where('office_id', $request->user()->office_id)->latest()->get();

        return response()->json(['data' => $keys]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:100']]);
        $plain = 'pk_'.Str::random(40);

        $key = OfficeApiKey::create([
            'office_id' => $request->user()->office_id,
            'name' => $data['name'],
            'key_hash' => hash('sha256', $plain),
            'key_prefix' => substr($plain, 0, 12),
            'abilities' => ['properties:read', 'properties:write'],
            'is_active' => true,
        ]);

        return response()->json([
            'data' => $key,
            'plain_key' => $plain,
            'message' => 'کلید فقط یک‌بار نمایش داده می‌شود.',
        ], 201);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        OfficeApiKey::where('office_id', $request->user()->office_id)->where('id', $id)->update(['is_active' => false]);

        return response()->json(['message' => 'کلید غیرفعال شد.']);
    }
}
