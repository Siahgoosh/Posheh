<?php

namespace App\Models\Communication;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommTelegramAccount extends Model
{
    protected $table = 'comm_telegram_accounts';

    protected $fillable = [
        'user_id', 'visitor_id', 'account_type', 'telegram_user_id',
        'telegram_chat_id', 'username', 'first_name', 'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function visitor(): BelongsTo
    {
        return $this->belongsTo(CommVisitor::class, 'visitor_id');
    }
}
