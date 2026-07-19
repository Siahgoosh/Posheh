<?php

namespace App\Models;

use App\Traits\BelongsToOffice;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Morilog\Jalali\Jalalian;

class PropertyVisit extends Model
{
    use BelongsToOffice;

    protected $appends = ['visit_at_jalali', 'status_label'];

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

    public function getVisitAtJalaliAttribute(): ?string
    {
        return $this->visit_at
            ? Jalalian::fromDateTime($this->visit_at)->format('Y/m/d H:i')
            : null;
    }

    public function getStatusLabelAttribute(): ?string
    {
        return match ($this->status) {
            'scheduled' => 'برنامه‌ریزی‌شده',
            'completed' => 'انجام‌شده',
            'cancelled' => 'لغو',
            default => $this->status,
        };
    }
}
