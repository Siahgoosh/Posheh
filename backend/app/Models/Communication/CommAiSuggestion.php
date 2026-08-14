<?php

namespace App\Models\Communication;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommAiSuggestion extends Model
{
    public $timestamps = false;

    protected $table = 'comm_ai_suggestions';

    protected $fillable = ['conversation_id', 'message_id', 'suggestions', 'tone'];

    protected function casts(): array
    {
        return [
            'suggestions' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(CommConversation::class, 'conversation_id');
    }
}
