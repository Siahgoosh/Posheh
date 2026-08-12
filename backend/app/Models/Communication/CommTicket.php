<?php

namespace App\Models\Communication;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CommTicket extends Model
{
    use SoftDeletes;

    protected $table = 'comm_tickets';

    protected $fillable = [
        'uuid', 'office_id', 'conversation_id', 'lead_id', 'assigned_to',
        'department', 'priority', 'status', 'subject', 'description',
        'email_alias', 'due_at', 'closed_at', 'sla',
    ];

    protected function casts(): array
    {
        return [
            'due_at' => 'datetime',
            'closed_at' => 'datetime',
            'sla' => 'array',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(CommConversation::class, 'conversation_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
