<?php

namespace App\Services\Ticket;

use App\Models\Ticket;
use App\Models\TicketReply;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class TicketService
{
    public const STATUS_LABELS = [
        'open' => 'باز',
        'in_progress' => 'در حال بررسی',
        'closed' => 'بسته شده',
    ];

    public const PRIORITY_LABELS = [
        'low' => 'کم',
        'medium' => 'متوسط',
        'high' => 'بالا',
    ];

    public function listForUser(User $user, ?string $status = null)
    {
        return Ticket::with(['replies.user', 'assignee:id,name'])
            ->where('office_id', $user->office_id)
            ->when(
                ! $user->canManageOffice() && ! $user->isSuperAdmin(),
                fn ($q) => $q->where('user_id', $user->id)
            )
            ->when($status, fn ($q) => $q->where('status', $status))
            ->latest()
            ->paginate(20)
            ->through(fn (Ticket $ticket) => $this->enrichTicket($ticket));
    }

    public function listAll(?string $status = null, ?string $priority = null)
    {
        return Ticket::with(['user', 'office', 'replies.user', 'assignee:id,name'])
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($priority, fn ($q) => $q->where('priority', $priority))
            ->latest()
            ->paginate(30)
            ->through(fn (Ticket $ticket) => $this->enrichTicket($ticket));
    }

    public function getForUser(User $user, int $ticketId): Ticket
    {
        $ticket = Ticket::with(['replies.user', 'user', 'assignee:id,name'])
            ->where('office_id', $user->office_id)
            ->findOrFail($ticketId);

        if (! $user->canManageOffice() && ! $user->isSuperAdmin() && $ticket->user_id !== $user->id) {
            throw ValidationException::withMessages(['ticket' => ['دسترسی ندارید.']]);
        }

        return $this->enrichTicket($ticket);
    }

    public function getForAdmin(int $ticketId): Ticket
    {
        $ticket = Ticket::with(['replies.user', 'user', 'office', 'assignee:id,name'])
            ->findOrFail($ticketId);

        return $this->enrichTicket($ticket, includeInternal: true);
    }

    public function create(User $user, array $data): Ticket
    {
        $ticket = Ticket::create([
            'office_id' => $user->office_id,
            'user_id' => $user->id,
            'ticket_number' => $this->generateTicketNumber(),
            'subject' => $data['subject'],
            'message' => $data['message'],
            'category' => $data['category'] ?? null,
            'priority' => $data['priority'] ?? 'medium',
            'status' => 'open',
        ]);

        return $this->enrichTicket($ticket->load(['user', 'replies']));
    }

    public function reply(User $user, int $ticketId, string $message, bool $isStaff = false, bool $isInternal = false): TicketReply
    {
        $ticket = Ticket::findOrFail($ticketId);

        if ($ticket->status === 'closed') {
            throw ValidationException::withMessages(['ticket' => ['تیکت بسته شده است.']]);
        }

        if (! $isStaff && $ticket->user_id !== $user->id) {
            throw ValidationException::withMessages(['ticket' => ['دسترسی ندارید.']]);
        }

        if ($isStaff) {
            $ticket->update([
                'status' => $isInternal ? $ticket->status : 'in_progress',
                'assigned_to' => $user->id,
            ]);
        } elseif ($ticket->status === 'in_progress') {
            $ticket->update(['status' => 'open']);
        }

        return TicketReply::create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'message' => $message,
            'is_staff' => $isStaff,
            'is_internal' => $isInternal,
        ])->load('user');
    }

    public function updateStatus(User $user, int $ticketId, string $status): Ticket
    {
        if (! $user->isPlatformStaff()) {
            throw ValidationException::withMessages(['ticket' => ['فقط پشتیبانی پلتفرم.']]);
        }

        $ticket = Ticket::findOrFail($ticketId);
        $ticket->update([
            'status' => $status,
            'closed_at' => $status === 'closed' ? now() : null,
        ]);

        return $this->enrichTicket($ticket->fresh(['replies.user', 'user', 'office']));
    }

    public function assign(User $user, int $ticketId, ?int $assigneeId): Ticket
    {
        if (! $user->isPlatformStaff()) {
            throw ValidationException::withMessages(['ticket' => ['فقط پشتیبانی پلتفرم.']]);
        }

        $ticket = Ticket::findOrFail($ticketId);
        $ticket->update([
            'assigned_to' => $assigneeId,
            'status' => $assigneeId ? 'in_progress' : $ticket->status,
        ]);

        return $this->enrichTicket($ticket->fresh(['assignee:id,name', 'user', 'office']));
    }

    private function generateTicketNumber(): string
    {
        do {
            $number = 'TKT-'.now()->format('ymd').'-'.strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
        } while (Ticket::where('ticket_number', $number)->exists());

        return $number;
    }

    private function enrichTicket(Ticket $ticket, bool $includeInternal = false): Ticket
    {
        $ticket->setAttribute('status_label', self::STATUS_LABELS[$ticket->status] ?? $ticket->status);
        $ticket->setAttribute('priority_label', self::PRIORITY_LABELS[$ticket->priority] ?? $ticket->priority);

        if (! $includeInternal && $ticket->relationLoaded('replies')) {
            $ticket->setRelation(
                'replies',
                $ticket->replies->filter(fn (TicketReply $reply) => ! $reply->is_internal)->values()
            );
        }

        return $ticket;
    }
}
