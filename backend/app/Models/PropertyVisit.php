<?php

namespace App\Models;

use App\Traits\BelongsToOffice;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PropertyVisit extends Model
{
    use BelongsToOffice;

    protected $fillable = [
        'office_id',
        'property_id',
        'customer_id',
        'assigned_to',
        'created_by',
        'visit_at',
        'duration_minutes',
        'status',
        'notes',
        'sms_reminder_sent',
    ];

    protected function casts(): array
    {
        return [
            'visit_at' => 'datetime',
            'sms_reminder_sent' => 'boolean',
        ];
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
