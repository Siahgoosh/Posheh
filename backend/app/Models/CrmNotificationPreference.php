<?php

namespace App\Models;

use App\Traits\BelongsToOffice;
use Illuminate\Database\Eloquent\Model;

class CrmNotificationPreference extends Model
{
    use BelongsToOffice;

    protected $fillable = [
        'office_id', 'user_id', 'category', 'in_app', 'sms', 'telegram', 'email', 'push', 'digest',
    ];

    protected function casts(): array
    {
        return [
            'in_app' => 'boolean', 'sms' => 'boolean', 'telegram' => 'boolean',
            'email' => 'boolean', 'push' => 'boolean',
        ];
    }
}
