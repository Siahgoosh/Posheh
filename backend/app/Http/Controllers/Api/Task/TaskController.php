<?php

namespace App\Http\Controllers\Api\Task;

use App\Http\Controllers\Controller;
use App\Http\Resources\TaskResource;
use App\Services\Task\TaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function __construct(
        private readonly TaskService $taskService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tasks = $this->taskService->list($request->user());

        return response()->json([
            'data' => TaskResource::collection($tasks),
            'meta' => [
                'current_page' => $tasks->currentPage(),
                'last_page' => $tasks->lastPage(),
                'total' => $tasks->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'property_id' => ['nullable', 'integer', 'exists:properties,id'],
            'priority' => ['nullable', 'string', 'in:low,medium,high'],
            'due_at' => ['nullable', 'date'],
        ]);

        $task = $this->taskService->create($request->user(), $request->all());

        return response()->json([
            'data' => new TaskResource($task),
            'message' => 'وظیفه ایجاد شد.',
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['nullable', 'string', 'in:pending,in_progress,completed,cancelled'],
            'priority' => ['nullable', 'string', 'in:low,medium,high'],
            'due_at' => ['nullable', 'date'],
        ]);

        $task = $this->taskService->update($request->user(), $id, $request->all());

        return response()->json([
            'data' => new TaskResource($task),
            'message' => 'وظیفه به‌روزرسانی شد.',
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $this->taskService->delete($request->user(), $id);

        return response()->json(['message' => 'وظیفه حذف شد.']);
    }
}
