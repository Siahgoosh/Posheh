<?php

namespace App\Modules\Communication\Application\Services;

use App\Models\Communication\CommConversation;
use App\Models\Communication\CommEmailThread;
use App\Models\Communication\CommTicket;
use Illuminate\Support\Str;

class TicketService
{
    public function __construct(private readonly CommunicationSettingsService $commSettings) {}

    public function createFromConversation(CommConversation $conversation, ?int $userId = null, array $data = []): CommTicket
    {
        $alias = 'ticket-'.Str::lower(Str::random(10)).'@'.$this->commSettings->emailInboundDomain();

        $ticket = CommTicket::create([
            'uuid' => (string) Str::uuid(),
            'office_id' => $conversation->office_id,
            'conversation_id' => $conversation->id,
            'lead_id' => $conversation->lead_id,
            'assigned_to' => $data['assigned_to'] ?? $conversation->assigned_to ?? $userId,
            'department' => $data['department'] ?? 'support',
            'priority' => $data['priority'] ?? 'normal',
            'status' => 'open',
            'subject' => $data['subject'] ?? $conversation->subject ?? 'تیکت پشتیبانی',
            'description' => $data['description'] ?? null,
            'email_alias' => $alias,
            'due_at' => $data['due_at'] ?? null,
        ]);

        $conversation->update(['ticket_id' => $ticket->id]);

        CommEmailThread::create([
            'conversation_id' => $conversation->id,
            'ticket_id' => $ticket->id,
            'alias_email' => $alias,
            'subject' => $ticket->subject,
        ]);

        return $ticket;
    }

    public function close(CommTicket $ticket, ?int $userId = null): CommTicket
    {
        $ticket->update(['status' => 'closed', 'closed_at' => now()]);
        if ($ticket->conversation) {
            $ticket->conversation->update(['status' => 'closed']);
        }

        return $ticket->fresh();
    }
}
