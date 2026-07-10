<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppRelease;
use Illuminate\Http\JsonResponse;

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
}
