<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BroadcastMessageRead extends Model
{
    protected $fillable = [
        'broadcast_message_id',
        'user_id',
        'read_at',
        'delivered_at',
    ];

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(BroadcastMessage::class, 'broadcast_message_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
