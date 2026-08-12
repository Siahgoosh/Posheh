<?php

namespace App\Models\Communication;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommAttachment extends Model
{
    protected $table = 'comm_attachments';

    protected $fillable = [
        'message_id', 'conversation_id', 'disk', 'path', 'original_name',
        'mime_type', 'size', 'message_type', 'meta',
    ];

    protected function casts(): array
    {
        return ['meta' => 'array'];
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(CommMessage::class, 'message_id');
    }
}
