<?php

namespace App\Models\Communication;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CommConversation extends Model
{
    use SoftDeletes;

    protected $table = 'comm_conversations';

    protected $fillable = [
        'uuid', 'office_id', 'visitor_id', 'lead_id', 'ticket_id', 'channel', 'status',
        'assigned_to', 'subject', 'external_chat_id', 'external_thread_id',
        'unread_visitor', 'unread_operator', 'last_message_at', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function visitor(): BelongsTo
    {
        return $this->belongsTo(CommVisitor::class, 'visitor_id');
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(CommLead::class, 'lead_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(CommMessage::class, 'conversation_id');
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(CommTicket::class, 'ticket_id');
    }
}
