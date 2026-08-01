<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DomainOrder extends Model
{
    protected $fillable = [
        'office_id',
        'requested_by',
        'domain_name',
        'is_available',
        'availability_note',
        'status',
        'price',
        'payment_id',
        'admin_notes',
        'purchased_at',
        'connected_at',
    ];

    protected function casts(): array
    {
        return [
            'purchased_at' => 'datetime',
            'connected_at' => 'datetime',
        ];
    }

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }
}
