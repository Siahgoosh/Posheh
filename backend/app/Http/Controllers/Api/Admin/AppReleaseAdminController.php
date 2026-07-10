<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppRelease;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AppReleaseAdminController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => AppRelease::orderByDesc('updated_at')->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);
        $release = AppRelease::create($data);

        return response()->json(['data' => $release], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $release = AppRelease::findOrFail($id);
        $release->update($this->validated($request));

        return response()->json(['data' => $release->fresh()]);
    }

    public function destroy(int $id): JsonResponse
    {
        AppRelease::findOrFail($id)->delete();

        return response()->json(['message' => 'نسخه حذف شد.']);
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'platform' => ['required', Rule::in(['android', 'windows', 'pwa'])],
            'version' => ['required', 'string', 'max:50'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'download_url' => ['required', 'string', 'max:500'],
            'file_size' => ['nullable', 'string', 'max:50'],
            'is_published' => ['sometimes', 'boolean'],
            'published_at' => ['nullable', 'date'],
        ]);

        if (($data['is_published'] ?? false) && empty($data['published_at'])) {
            $data['published_at'] = now();
        }

        if (! ($data['is_published'] ?? false)) {
            $data['published_at'] = null;
        }

        return $data;
    }
}
