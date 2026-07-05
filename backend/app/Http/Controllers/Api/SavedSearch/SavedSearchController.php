<?php

namespace App\Http\Controllers\Api\SavedSearch;

use App\Http\Controllers\Controller;
use App\Services\SavedSearch\SavedSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SavedSearchController extends Controller
{
    public function __construct(
        private readonly SavedSearchService $savedSearchService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->savedSearchService->list($request->user()),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'filters' => ['nullable', 'array'],
            'notify_on_match' => ['nullable', 'boolean'],
        ]);

        $search = $this->savedSearchService->create($request->user(), $request->all());

        return response()->json([
            'data' => $search,
            'message' => 'جستجو ذخیره شد.',
        ], 201);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $this->savedSearchService->delete($request->user(), $id);

        return response()->json(['message' => 'جستجو حذف شد.']);
    }
}
