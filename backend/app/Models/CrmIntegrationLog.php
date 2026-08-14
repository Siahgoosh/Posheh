<?php

namespace App\Models;

use App\Traits\BelongsToOffice;
use Illuminate\Database\Eloquent\Model;

class CrmIntegrationLog extends Model
{
    use BelongsToOffice;

    protected $fillable = [
        'office_id', 'integration_id', 'provider', 'direction', 'status',
        'subject_type', 'subject_id', 'payload_meta', 'error',
    ];

    protected function casts(): array
    {
        return ['payload_meta' => 'array'];
    }
}
