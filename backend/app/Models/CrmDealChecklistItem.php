<?php

namespace App\Models;

use App\Traits\BelongsToOffice;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmDealChecklistItem extends Model
{
    use BelongsToOffice;

    protected $fillable = [
        'office_id', 'crm_deal_id', 'key', 'label', 'is_done', 'sort_order', 'done_at', 'done_by',
    ];

    protected function casts(): array
    {
        return [
            'is_done' => 'boolean',
            'done_at' => 'datetime',
        ];
    }

    public function deal(): BelongsTo
    {
        return $this->belongsTo(CrmDeal::class, 'crm_deal_id');
    }
}
