<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Morilog\Jalali\Jalalian;

class TaskResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'priority' => $this->priority,
            'status' => $this->status,
            'due_at' => $this->due_at?->toIso8601String(),
            'due_at_jalali' => $this->due_at
                ? Jalalian::fromDateTime($this->due_at)->format('Y/m/d')
                : null,
            'assignee' => new UserResource($this->whenLoaded('assignee')),
            'property' => new PropertyResource($this->whenLoaded('property')),
        ];
    }
}
