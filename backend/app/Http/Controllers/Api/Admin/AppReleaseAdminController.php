<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppRelease;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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

    public function uploadFile(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'max:204800'],
            'platform' => ['required', Rule::in(['android', 'windows', 'pwa'])],
        ]);

        $platform = $request->input('platform');
        $file = $request->file('file');
        $ext = strtolower($file->getClientOriginalExtension() ?: 'bin');

        $allowed = match ($platform) {
            'android' => ['apk', 'aab'],
            'windows' => ['exe', 'msi', 'zip', 'msix'],
            'pwa' => ['zip', 'json', 'webmanifest'],
            default => [],
        };

        if ($allowed !== [] && ! in_array($ext, $allowed, true)) {
            return response()->json([
                'message' => 'فرمت فایل برای این پلتفرم مجاز نیست.',
            ], 422);
        }

        $path = $file->store("downloads/{$platform}", 'public');
        $url = Storage::disk('public')->url($path);
        $bytes = $file->getSize() ?: 0;

        return response()->json([
            'data' => [
                'file_path' => $path,
                'download_url' => $url,
                'file_size' => $this->formatBytes($bytes),
                'original_name' => $file->getClientOriginalName(),
            ],
        ], 201);
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 1).' MB';
        }

        if ($bytes >= 1024) {
            return round($bytes / 1024, 1).' KB';
        }

        return $bytes.' B';
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'platform' => ['required', Rule::in(['android', 'windows', 'pwa'])],
            'version' => ['required', 'string', 'max:50'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'download_url' => ['nullable', 'string', 'max:500'],
            'file_path' => ['nullable', 'string', 'max:500'],
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

        if (empty($data['download_url']) && ! empty($data['file_path'])) {
            $data['download_url'] = Storage::disk('public')->url($data['file_path']);
        }

        if (empty($data['download_url']) && empty($data['file_path'])) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'download_url' => ['فایل را آپلود کنید یا لینک دانلود وارد کنید.'],
            ]);
        }

        return $data;
    }
}
