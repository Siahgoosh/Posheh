<?php

namespace App\Modules\Communication\Application\Services;

use App\Models\Communication\CommConversation;
use App\Models\Communication\CommLead;
use App\Models\Communication\CommVisitorSession;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class InboxService
{
    public function conversations(?string $status = null, int $perPage = 30): LengthAwarePaginator
    {
        return CommConversation::query()
            ->with(['visitor', 'lead', 'assignee:id,name'])
            ->with(['messages' => fn ($q) => $q->latest()->limit(1)])
            ->when($status, fn ($q, $s) => $q->where('status', $s))
            ->orderByDesc('last_message_at')
            ->paginate($perPage);
    }

    public function conversationDetail(string $uuid): ?CommConversation
    {
        return CommConversation::where('uuid', $uuid)
            ->with([
                'visitor',
                'lead.stage',
                'lead.tags',
                'lead.notes.user:id,name',
                'lead.tasks',
                'assignee:id,name',
                'ticket',
                'messages' => fn ($q) => $q->orderBy('created_at'),
                'messages.commAttachments',
            ])
            ->first();
    }

    /** @return array<string, int> */
    public function dashboardStats(): array
    {
        return [
            'active_chats' => CommConversation::where('status', 'open')->count(),
            'waiting' => CommConversation::where('status', 'waiting')->count(),
            'closed_today' => CommConversation::where('status', 'closed')
                ->whereDate('updated_at', today())->count(),
            'new_leads_today' => CommLead::whereDate('created_at', today())->count(),
            'online_visitors' => CommVisitorSession::where('is_online', true)
                ->where('last_activity_at', '>=', now()->subMinutes(5))
                ->count(),
            'unread_messages' => CommConversation::sum('unread_operator'),
        ];
    }
}
