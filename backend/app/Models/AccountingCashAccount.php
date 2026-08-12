<?php

namespace App\Models;

use App\Traits\BelongsToOffice;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AccountingCashAccount extends Model
{
    use BelongsToOffice, SoftDeletes;

    protected $fillable = [
        'office_id', 'ledger_account_id', 'name', 'kind', 'bank_name',
        'account_holder', 'account_number', 'card_number', 'iban',
        'opening_balance', 'opening_date', 'is_active', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'opening_balance' => 'integer',
            'opening_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function ledgerAccount(): BelongsTo
    {
        return $this->belongsTo(AccountingAccount::class, 'ledger_account_id');
    }
}
