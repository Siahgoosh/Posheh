<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlatformLead extends Model
{
    public const STAGES = [
        'new' => 'سرنخ جدید',
        'contacted' => 'تماس گرفته',
        'qualified' => 'واجد شرایط',
        'demo' => 'دمو رزرو شده',
        'won' => 'مشتری شد',
        'lost' => 'از دست رفت',
    ];

    protected $fillable = [
        'source',
        'source_id',
        'name',
        'mobile',
        'email',
        'message',
        'office_id',
        'property_id',
        'stage',
        'assigned_to',
        'notes',
        'follow_up_at',
    ];

    protected function casts(): array
    {
        return [
            'follow_up_at' => 'datetime',
        ];
    }

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
