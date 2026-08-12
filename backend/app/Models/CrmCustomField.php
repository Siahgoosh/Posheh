<?php

namespace App\Models;

use App\Traits\BelongsToOffice;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CrmCustomField extends Model
{
    use BelongsToOffice;

    protected $fillable = [
        'office_id', 'entity', 'key', 'label', 'type', 'options',
        'is_required', 'sort_order', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'is_required' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function values(): HasMany
    {
        return $this->hasMany(CrmCustomFieldValue::class, 'custom_field_id');
    }
}
