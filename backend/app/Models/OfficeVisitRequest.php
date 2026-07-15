<?php

namespace App\Models;

use App\Traits\BelongsToOffice;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfficeVisitRequest extends Model
{
    use BelongsToOffice;

    protected $fillable = [
        'office_id', 'property_id', 'name', 'mobile', 'message', 'status',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }
}
