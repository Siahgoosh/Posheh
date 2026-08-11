<?php

namespace App\Models;

use App\Traits\BelongsToOffice;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AccountingPosTerminal extends Model
{
    use BelongsToOffice, SoftDeletes;

    protected $fillable = [
        'office_id', 'cash_account_id', 'name', 'bank_name',
        'terminal_id', 'merchant_id', 'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function cashAccount(): BelongsTo
    {
        return $this->belongsTo(AccountingCashAccount::class, 'cash_account_id');
    }
}
