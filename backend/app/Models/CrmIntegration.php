<?php

namespace App\Models;

use App\Traits\BelongsToOffice;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CrmIntegration extends Model
{
    use BelongsToOffice;

    protected $fillable = [
        'office_id', 'provider', 'name', 'is_enabled', 'status',
        'credentials', 'settings', 'last_error', 'last_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'credentials' => 'array',
            'settings' => 'array',
            'last_synced_at' => 'datetime',
        ];
    }

    public function logs(): HasMany
    {
        return $this->hasMany(CrmIntegrationLog::class, 'integration_id');
    }
}
