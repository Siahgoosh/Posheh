<?php

namespace App\Models;

use App\Traits\BelongsToOffice;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountingSettlementItem extends Model
{
    use BelongsToOffice;

    protected $fillable = [
        'office_id', 'settlement_id', 'commission_id', 'amount', 'label',
    ];

    protected function casts(): array
    {
        return ['amount' => 'integer'];
    }

    public function settlement(): BelongsTo
    {
        return $this->belongsTo(AccountingSettlement::class, 'settlement_id');
    }

    public function commission(): BelongsTo
    {
        return $this->belongsTo(Commission::class);
    }
}
