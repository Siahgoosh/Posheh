<?php

namespace App\Models\Communication;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommMessageStatus extends Model
{
    public $timestamps = false;

    protected $table = 'comm_message_status';

    protected $fillable = ['message_id', 'channel', 'status', 'external_id', 'meta'];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'updated_at' => 'datetime',
        ];
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(CommMessage::class, 'message_id');
    }
}
