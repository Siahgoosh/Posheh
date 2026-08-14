<?php

namespace App\Models;

use App\Traits\BelongsToOffice;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmPropertyFeedback extends Model
{
    use BelongsToOffice;

    protected $fillable = [
        'office_id', 'customer_id', 'property_id', 'crm_deal_id', 'property_visit_id',
        'created_by', 'reaction', 'rating', 'likelihood', 'comment',
    ];

    protected function casts(): array
    {
        return ['rating' => 'integer'];
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
