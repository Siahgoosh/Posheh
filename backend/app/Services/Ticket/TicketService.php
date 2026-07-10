<?php

namespace App\Services\Ticket;

use App\Models\Ticket;
use App\Models\TicketReply;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class TicketService
{
    public function listForUser(User $user)
    {
        return Ticket::with(['replies.user'])
            ->where('office_id', $user->office_id)
            ->when(! $user->isSuperAdmin(), fn ($q) => $q->where('user_id', $user->id))
            ->latest()
            ->paginate(20);
    }

    public function listAll()
    {
        return Ticket::with(['user', 'office', 'replies'])
            ->latest()
            ->paginate(30);
    }

    public function create(User $user, array $data): Ticket
    {
        return Ticket::create([
            'office_id' => $user->office_id,
            'user_id' => $user->id,
            'subject' => $data['subject'],
            'message' => $data['message'],
            'priority' => $data['priority'] ?? 'medium',
            'status' => 'open',
        ]);
    }

    public function reply(User $user, int $ticketId, string $message, bool $isStaff = false): TicketReply
    {
        $ticket = Ticket::findOrFail($ticketId);

        if (! $isStaff && $ticket->user_id !== $user->id) {
            throw ValidationException::withMessages(['ticket' => ['دسترسی ندارید.']]);
        }

        if ($isStaff) {
            $ticket->update(['status' => 'in_progress', 'assigned_to' => $user->id]);
        }

        return TicketReply::create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'message' => $message,
            'is_staff' => $isStaff,
        ]);
    }

    public function updateStatus(User $user, int $ticketId, string $status): Ticket
    {
        if (! $user->isSuperAdmin()) {
            throw ValidationException::withMessages(['ticket' => ['فقط مدیر سیستم.']]);
        }

        $ticket = Ticket::findOrFail($ticketId);
        $ticket->update([
            'status' => $status,
            'closed_at' => $status === 'closed' ? now() : null,
        ]);

        return $ticket->fresh();
    }
}
