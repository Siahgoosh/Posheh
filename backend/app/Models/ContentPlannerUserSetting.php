<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContentPlannerUserSetting extends Model
{
    protected $table = 'content_planner_user_settings';

    protected $fillable = [
        'user_id',
        'office_id',
        'sms_reminder_enabled',
        'in_app_notification_enabled',
        'sms_mobile',
    ];

    protected function casts(): array
    {
        return [
            'sms_reminder_enabled' => 'boolean',
            'in_app_notification_enabled' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
