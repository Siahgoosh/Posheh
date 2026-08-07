<?php

namespace App\Models\Communication;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CommMessage extends Model
{
    use SoftDeletes;

    protected $table = 'comm_messages';

    protected $fillable = [
        'conversation_id', 'sender_type', 'sender_id', 'body', 'body_html',
        'message_type', 'attachments', 'reply_to_id', 'is_internal',
        'read_by_visitor_at', 'read_by_operator_at', 'delivered_at', 'edited_at', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            'attachments' => 'array',
            'is_internal' => 'boolean',
            'read_by_visitor_at' => 'datetime',
            'read_by_operator_at' => 'datetime',
            'delivered_at' => 'datetime',
            'edited_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(CommConversation::class, 'conversation_id');
    }

    public function replyTo(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reply_to_id');
    }
}
