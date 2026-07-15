<?php

namespace App\Http\Controllers\Api\Owner;

use App\Http\Controllers\Controller;
use App\Services\Owner\OwnerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OwnerController extends Controller
{
    public function __construct(private readonly OwnerService $ownerService) {}

    public function index(Request $request): JsonResponse
    {
        $owners = $this->ownerService->list($request->user(), $request->query('q'));

        return response()->json([
            'data' => $owners->items(),
            'meta' => [
                'current_page' => $owners->currentPage(),
                'last_page' => $owners->lastPage(),
                'total' => $owners->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'mobile' => ['nullable', 'string', 'max:20'],
            'national_id' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);

        $owner = $this->ownerService->create($request->user(), $data);

        return response()->json(['data' => $owner, 'message' => 'مالک ثبت شد.'], 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        return response()->json(['data' => $this->ownerService->find($request->user(), $id)]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'mobile' => ['nullable', 'string', 'max:20'],
            'national_id' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);

        $owner = $this->ownerService->update($request->user(), $id, $data);

        return response()->json(['data' => $owner, 'message' => 'مالک ویرایش شد.']);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $this->ownerService->delete($request->user(), $id);

        return response()->json(['message' => 'مالک حذف شد.']);
    }
}
