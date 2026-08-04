<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VirtualTourMediaController extends Controller
{
    public function show(Request $request): StreamedResponse|\Illuminate\Http\Response
    {
        if (! $request->hasValidSignature()) {
            abort(403, 'Invalid or expired media URL.');
        }

        $path = $request->query('path');
        if (! $path || ! is_string($path)) {
            abort(400);
        }

        $path = str_replace(['..', '\\'], '', $path);
        $disk = Storage::disk('public');

        if (! $disk->exists($path)) {
            abort(404);
        }

        $mime = $disk->mimeType($path) ?: 'application/octet-stream';
        $stream = $disk->readStream($path);

        return response()->stream(function () use ($stream) {
            fpassthru($stream);
            if (is_resource($stream)) {
                fclose($stream);
            }
        }, 200, [
            'Content-Type' => $mime,
            'Cache-Control' => 'private, max-age=3600',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
