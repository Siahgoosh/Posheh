<?php

namespace App\Models;

use App\Traits\BelongsToOffice;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AccountingSettlement extends Model
{
    use BelongsToOffice;

    protected $fillable = [
        'office_id', 'settlement_number', 'kind', 'amount', 'settlement_date',
        'payment_method', 'cash_account_id', 'cheque_id', 'consultant_id',
        'party_type', 'party_id', 'crm_deal_id', 'journal_entry_id',
        'reference', 'description', 'status', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'settlement_date' => 'date',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(AccountingSettlementItem::class, 'settlement_id');
    }

    public function consultant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'consultant_id');
    }

    public function party(): MorphTo
    {
        return $this->morphTo();
    }
}
