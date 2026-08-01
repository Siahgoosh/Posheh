<?php

namespace App\Services\Office;

use App\Models\TeamChatMessage;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class TeamChatService
{
    public function list(User $user, ?int $afterId = null, int $limit = 50)
    {
        if (! $user->office_id) {
            throw ValidationException::withMessages(['office' => ['دفتر یافت نشد.']]);
        }

        $query = TeamChatMessage::with('user:id,name,role')
            ->where('office_id', $user->office_id)
            ->orderByDesc('id')
            ->limit(min($limit, 100));

        if ($afterId) {
            $query->where('id', '>', $afterId);
        }

        return $query->get()->reverse()->values();
    }

    public function send(User $user, string $message): TeamChatMessage
    {
        if (! $user->office_id) {
            throw ValidationException::withMessages(['office' => ['دفتر یافت نشد.']]);
        }

        $message = trim($message);
        if ($message === '') {
            throw ValidationException::withMessages(['message' => ['پیام خالی است.']]);
        }

        return TeamChatMessage::create([
            'office_id' => $user->office_id,
            'user_id' => $user->id,
            'message' => $message,
        ])->load('user:id,name,role');
    }
}
