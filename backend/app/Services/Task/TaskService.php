<?php

namespace App\Services\Task;

use App\Models\Task;
use App\Models\User;
use App\Services\Notification\NotificationService;
use Illuminate\Validation\ValidationException;

class TaskService
{
    public function __construct(
        private readonly NotificationService $notificationService,
    ) {}

    public function list(User $user)
    {
        return Task::where('office_id', $user->office_id)
            ->with(['assignee', 'property'])
            ->latest()
            ->paginate(20);
    }

    public function create(User $user, array $data): Task
    {
        $task = Task::create([
            'office_id' => $user->office_id,
            'created_by' => $user->id,
            'assigned_to' => $data['assigned_to'] ?? $user->id,
            'property_id' => $data['property_id'] ?? null,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'priority' => $data['priority'] ?? 'medium',
            'status' => 'pending',
            'due_at' => $data['due_at'] ?? null,
        ]);

        if ($task->assigned_to && $task->assigned_to !== $user->id) {
            $assignee = User::find($task->assigned_to);
            if ($assignee) {
                $this->notificationService->send(
                    $assignee,
                    'وظیفه جدید',
                    "{$user->name} وظیفه «{$task->title}» را به شما اختصاص داد.",
                    'task',
                    ['task_id' => $task->id]
                );
            }
        }

        return $task->load(['assignee', 'property']);
    }

    public function update(User $user, int $id, array $data): Task
    {
        $task = $this->findForUser($user, $id);
        $task->update($data);

        if (($data['status'] ?? null) === 'completed' && ! $task->completed_at) {
            $task->update(['completed_at' => now()]);
        }

        return $task->load(['assignee', 'property']);
    }

    public function delete(User $user, int $id): void
    {
        $this->findForUser($user, $id)->delete();
    }

    private function findForUser(User $user, int $id): Task
    {
        $task = Task::where('office_id', $user->office_id)->find($id);

        if (! $task) {
            throw ValidationException::withMessages([
                'task' => ['وظیفه یافت نشد.'],
            ]);
        }

        return $task;
    }
}
