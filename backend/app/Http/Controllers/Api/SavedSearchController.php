<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SavedSearch\SavedSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SavedSearchController extends Controller
{
    public function __construct(private readonly SavedSearchService $searches) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->searches->list($request->user())]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'filters' => ['nullable', 'array'],
            'notify_on_match' => ['nullable', 'boolean'],
        ]);

        $search = $this->searches->create($request->user(), $data);

        return response()->json(['data' => $search, 'message' => 'جستجو ذخیره شد.'], 201);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $this->searches->delete($request->user(), $id);

        return response()->json(['message' => 'جستجو حذف شد.']);
    }

    public function run(Request $request, int $id): JsonResponse
    {
        $result = $this->searches->run($request->user(), $id);

        return response()->json(['data' => $result]);
    }
}
