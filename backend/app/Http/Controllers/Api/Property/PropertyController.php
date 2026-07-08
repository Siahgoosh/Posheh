<?php

namespace App\Http\Controllers\Api\Property;

use App\DTOs\Property\CreatePropertyDTO;
use App\DTOs\Property\PropertySearchDTO;
use App\Http\Controllers\Controller;
use App\Http\Resources\PropertyResource;
use App\Services\Property\PropertyService;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\Property\UpdatePropertyRequest;

class PropertyController extends Controller
{
    public function __construct(
        private readonly PropertyService $propertyService,
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

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'code' => ['required', 'string', 'max:50'],
            'type' => ['required', 'string'],
            'permission' => ['nullable', 'string'],
            'owner_name' => ['nullable', 'string', 'max:255'],
            'owner_mobile' => ['nullable', 'string'],
            'price' => ['nullable', 'integer', 'min:0'],
            'area' => ['nullable', 'numeric', 'min:0'],
            'rooms' => ['nullable', 'integer', 'min:0'],
            'description' => ['nullable', 'string'],
            'city' => ['nullable', 'string'],
            'district' => ['nullable', 'string'],
            'address' => ['nullable', 'string'],
        ]);

        $property = $this->propertyService->create(
            $request->user(),
            CreatePropertyDTO::fromArray($request->all())
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
}
