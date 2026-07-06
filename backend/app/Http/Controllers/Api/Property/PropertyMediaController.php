<?php

namespace App\Http\Controllers\Api\Property;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\PropertyMedia;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PropertyMediaController extends Controller
{
    public function store(Request $request, int $propertyId): JsonResponse
    {
        $property = Property::where('office_id', $request->user()->office_id)->findOrFail($propertyId);

        $request->validate([
            'file' => ['required', 'file', 'max:10240', 'mimes:jpg,jpeg,png,webp,pdf'],
            'type' => ['nullable', 'in:image,video,document'],
            'is_cover' => ['nullable', 'boolean'],
        ]);

        $file = $request->file('file');
        $path = $file->store("properties/{$property->office_id}/{$property->id}", 'public');

        if ($request->boolean('is_cover')) {
            $property->media()->update(['is_cover' => false]);
        }

        $media = PropertyMedia::create([
            'property_id' => $property->id,
            'type' => $request->input('type', 'image'),
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'is_cover' => $request->boolean('is_cover'),
            'sort_order' => $property->media()->count(),
        ]);

        return response()->json(['data' => $media, 'url' => Storage::disk('public')->url($path)], 201);
    }

    public function destroy(Request $request, int $propertyId, int $mediaId): JsonResponse
    {
        $property = Property::where('office_id', $request->user()->office_id)->findOrFail($propertyId);
        $media = $property->media()->findOrFail($mediaId);
        Storage::disk('public')->delete($media->path);
        $media->delete();

        return response()->json(['message' => 'فایل حذف شد.']);
    }
}
