<?php

namespace App\Models\Communication;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommChannelMessageMap extends Model
{
    protected $table = 'comm_channel_message_map';

    protected $fillable = [
        'channel', 'external_message_id', 'conversation_id', 'message_id', 'map_type',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(CommConversation::class, 'conversation_id');
    }
}
