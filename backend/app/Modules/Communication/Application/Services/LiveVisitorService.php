<?php

namespace App\Modules\Communication\Application\Services;

use App\Models\Communication\CommVisitorSession;
use Illuminate\Support\Collection;

class LiveVisitorService
{
    /** @return Collection<int, CommVisitorSession> */
    public function online(int $limit = 50): Collection
    {
        $ttl = (int) config('communication.visitor_session_ttl_minutes', 30);

        return CommVisitorSession::query()
            ->with(['visitor'])
            ->where('is_online', true)
            ->where('last_activity_at', '>=', now()->subMinutes($ttl))
            ->orderByDesc('last_activity_at')
            ->limit($limit)
            ->get();
    }

    public function markStaleOffline(): int
    {
        $ttl = (int) config('communication.visitor_session_ttl_minutes', 30);

        return CommVisitorSession::query()
            ->where('is_online', true)
            ->where('last_activity_at', '<', now()->subMinutes($ttl))
            ->update(['is_online' => false, 'ended_at' => now()]);
    }
}
