<?php

namespace App\Models;

use App\Traits\BelongsToOffice;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmNeedProfile extends Model
{
    use BelongsToOffice;

    protected $fillable = [
        'office_id', 'customer_id', 'crm_deal_id', 'transaction_type', 'property_type', 'purpose',
        'preferred_locations', 'excluded_locations', 'budget_min', 'budget_max',
        'min_area', 'max_area', 'bedrooms', 'max_building_age', 'floor_preference',
        'parking_required', 'elevator_preferred', 'storage_preferred', 'balcony_preferred',
        'document_type', 'occupancy', 'payment_ability', 'down_payment', 'monthly_payment',
        'deposit', 'monthly_rent', 'purchase_timeline', 'urgency',
        'preferred_features', 'excluded_features', 'priority_weights', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'preferred_locations' => 'array',
            'excluded_locations' => 'array',
            'preferred_features' => 'array',
            'excluded_features' => 'array',
            'priority_weights' => 'array',
            'parking_required' => 'boolean',
            'elevator_preferred' => 'boolean',
            'storage_preferred' => 'boolean',
            'balcony_preferred' => 'boolean',
            'budget_min' => 'integer',
            'budget_max' => 'integer',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function deal(): BelongsTo
    {
        return $this->belongsTo(CrmDeal::class, 'crm_deal_id');
    }
}
