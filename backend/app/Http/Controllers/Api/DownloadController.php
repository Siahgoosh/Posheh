<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppRelease;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DownloadController extends Controller
{
    public function index(): JsonResponse
    {
        $releases = AppRelease::published()
            ->orderByDesc('published_at')
            ->get()
            ->groupBy('platform')
            ->map(fn ($items) => $items->map(fn (AppRelease $release) => [
                'id' => $release->id,
                'platform' => $release->platform,
                'version' => $release->version,
                'title' => $release->title,
                'description' => $release->description,
                'download_url' => $release->download_url,
                'file_size' => $release->file_size,
                'published_at' => $release->published_at?->toIso8601String(),
            ])->values());

        return response()->json(['data' => $releases]);
    }

    /**
     * Fallback file serve when static nginx path misses (e.g. dist not rebuilt).
     */
    public function file(string $filename): BinaryFileResponse
    {
        $safe = basename($filename);
        if (! preg_match('/^posheh-(android\.apk|windows\.zip)$/i', $safe)) {
            abort(404);
        }

        $paths = [
            public_path('downloads/'.$safe),
            base_path('../frontend/public/downloads/'.$safe),
            base_path('../frontend/dist/downloads/'.$safe),
        ];

        foreach ($paths as $path) {
            if (File::isFile($path) && File::size($path) > 1024) {
                return response()->download($path, $safe);
            }
        }

        // Admin-uploaded release in storage
        $platform = str_contains($safe, 'android') ? 'android' : 'windows';
        $release = AppRelease::published()
            ->where('platform', $platform)
            ->orderByDesc('published_at')
            ->first();

        if ($release && str_starts_with($release->download_url, '/storage/')) {
            $storagePath = str_replace('/storage/', '', $release->download_url);
            if (Storage::disk('public')->exists($storagePath)) {
                return response()->download(Storage::disk('public')->path($storagePath), $safe);
            }
        }

        abort(404, 'فایل دانلود یافت نشد.');
    }
}
