<?php

namespace App\Models\Communication;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommAiSummary extends Model
{
    public $timestamps = false;

    protected $table = 'comm_ai_summaries';

    protected $fillable = ['conversation_id', 'summary', 'category', 'sentiment'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(CommConversation::class, 'conversation_id');
    }
}
