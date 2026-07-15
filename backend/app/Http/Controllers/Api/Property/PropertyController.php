<?php

namespace App\Http\Controllers\Api\Property;

use App\DTOs\Property\CreatePropertyDTO;
use App\DTOs\Property\PropertySearchDTO;
use App\Http\Controllers\Controller;
use App\Http\Resources\PropertyResource;
use App\Services\Property\PropertyExportService;
use App\Services\Property\PropertyService;
use App\Services\Property\PropertyShareService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Requests\Property\StorePropertyRequest;
use App\Http\Requests\Property\UpdatePropertyRequest;

class PropertyController extends Controller
{
    public function __construct(
        private readonly PropertyService $propertyService,
        private readonly PropertyExportService $exportService,
        private readonly PropertyShareService $shareService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $dto = PropertySearchDTO::fromRequest($request->all());
        $properties = $this->propertyService->list($request->user(), $dto);

        return response()->json([
            'data' => PropertyResource::collection($properties),
            'meta' => [
                'current_page' => $properties->currentPage(),
                'last_page' => $properties->lastPage(),
                'per_page' => $properties->perPage(),
                'total' => $properties->total(),
            ],
        ]);
    }

    public function store(StorePropertyRequest $request): JsonResponse
    {
        $property = $this->propertyService->create(
            $request->user(),
            CreatePropertyDTO::fromArray($request->validated())
        );

        return response()->json([
            'data' => new PropertyResource($property),
            'message' => 'ملک با موفقیت ثبت شد.',
        ], 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $property = $this->propertyService->find($request->user(), $id);

        return response()->json([
            'data' => new PropertyResource($property),
        ]);
    }

    public function update(UpdatePropertyRequest $request, int $id): JsonResponse
    {
        $property = $this->propertyService->update($request->user(), $id, $request->validated());

        return response()->json([
            'data' => new PropertyResource($property),
            'message' => 'ملک با موفقیت ویرایش شد.',
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $this->propertyService->delete($request->user(), $id);

        return response()->json(['message' => 'ملک با موفقیت حذف شد.']);
    }

    public function similar(Request $request, int $id): JsonResponse
    {
        $properties = $this->propertyService->getSimilar($request->user(), $id);

        return response()->json([
            'data' => PropertyResource::collection($properties),
        ]);
    }

    public function toggleFavorite(Request $request, int $id): JsonResponse
    {
        $result = $this->propertyService->toggleFavorite($request->user(), $id);

        return response()->json($result);
    }

    public function uploadMedia(Request $request, int $id): JsonResponse
    {
        $request->validate(['image' => ['required', 'image', 'max:10240'], 'is_cover' => ['nullable', 'boolean']]);
        $media = $this->propertyService->uploadMedia(
            $request->user(),
            $id,
            $request->file('image'),
            (bool) $request->boolean('is_cover')
        );

        return response()->json(['data' => $media, 'url' => url('storage/'.$media->path)], 201);
    }

    public function deleteMedia(Request $request, int $id, int $mediaId): JsonResponse
    {
        $this->propertyService->deleteMedia($request->user(), $id, $mediaId);

        return response()->json(['message' => 'تصویر حذف شد.']);
    }

    public function setCoverMedia(Request $request, int $id, int $mediaId): JsonResponse
    {
        $this->propertyService->setCoverMedia($request->user(), $id, $mediaId);

        return response()->json(['message' => 'کاور تنظیم شد.']);
    }

    public function export(Request $request)
    {
        return $this->exportService->export($request->user());
    }

    public function import(Request $request): JsonResponse
    {
        $request->validate(['file' => ['required', 'file', 'mimes:xlsx,xls,csv']]);

        return response()->json($this->exportService->import($request->user(), $request->file('file')));
    }

    public function shareMessage(Request $request, int $id): JsonResponse
    {
        $property = $this->propertyService->find($request->user(), $id);
        $property->load(['media', 'type', 'property_category']);
        $officeName = $request->user()->office?->name;

        return response()->json([
            'data' => [
                'message' => $this->shareService->buildMessage($property, $officeName),
                'ad_copy' => $this->shareService->buildAdCopy($property),
                'quality_score' => $this->shareService->qualityScore($property),
            ],
        ]);
    }

    public function share(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'recipient_mobile' => ['required', 'string', 'regex:/^09\d{9}$/'],
            'channel' => ['required', 'string', 'in:whatsapp,telegram,sms'],
        ]);

        $property = $this->propertyService->find($request->user(), $id);
        $property->load(['media', 'type', 'property_category']);
        $message = $this->shareService->buildMessage($property, $request->user()->office?->name);
        $links = $this->shareService->shareLinks($message, $data['recipient_mobile']);

        $this->shareService->logShare(
            $request->user(),
            $property,
            $data['channel'],
            $data['recipient_mobile']
        );

        return response()->json([
            'data' => [
                'url' => $links[$data['channel']] ?? null,
                'message' => $message,
            ],
        ]);
    }
}
