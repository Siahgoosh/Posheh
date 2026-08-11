<?php

namespace App\Models\Communication;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommEmailThread extends Model
{
    protected $table = 'comm_email_threads';

    protected $fillable = ['conversation_id', 'ticket_id', 'alias_email', 'subject'];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(CommConversation::class, 'conversation_id');
    }
}
