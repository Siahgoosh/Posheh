<?php

namespace App\Models;

use App\Traits\BelongsToOffice;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmNotification extends Model
{
    use BelongsToOffice;

    protected $fillable = [
        'office_id', 'user_id', 'category', 'title', 'body', 'link', 'meta', 'read_at',
    ];

    protected function casts(): array
    {
        return ['meta' => 'array', 'read_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
