<?php

namespace App\Models\Communication;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommEmailMessage extends Model
{
    protected $table = 'comm_email_messages';

    protected $fillable = [
        'thread_id', 'message_id', 'direction', 'from_email', 'to_email',
        'subject', 'body_text', 'body_html', 'external_id',
    ];

    public function thread(): BelongsTo
    {
        return $this->belongsTo(CommEmailThread::class, 'thread_id');
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(CommMessage::class, 'message_id');
    }
}
