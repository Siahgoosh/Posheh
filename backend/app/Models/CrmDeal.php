<?php

namespace App\Models;

use App\Traits\BelongsToOffice;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmDeal extends Model
{
    use BelongsToOffice;

    protected $fillable = [
        'office_id', 'assigned_to', 'property_id', 'title',
        'contact_name', 'contact_mobile', 'stage', 'value', 'notes', 'expected_close_at',
    ];

    protected function casts(): array
    {
        return ['expected_close_at' => 'datetime'];
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }
}
