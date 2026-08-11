<?php

namespace App\Models;

use App\Traits\BelongsToOffice;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AccountingTransaction extends Model
{
    use BelongsToOffice;

    protected $fillable = [
        'office_id', 'transaction_number', 'created_by', 'approved_by',
        'property_id', 'crm_deal_id', 'commission_id', 'consultant_id',
        'account_id', 'cash_account_id', 'pos_terminal_id', 'journal_entry_id',
        'type', 'status', 'category', 'payment_method', 'amount', 'title',
        'description', 'transaction_date', 'reference',
        'party_type', 'party_id', 'voided_at', 'voided_by', 'void_reason',
    ];

    protected function casts(): array
    {
        return [
            'transaction_date' => 'date',
            'amount' => 'integer',
            'voided_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(AccountingAccount::class, 'account_id');
    }

    public function cashAccount(): BelongsTo
    {
        return $this->belongsTo(AccountingCashAccount::class, 'cash_account_id');
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(AccountingJournalEntry::class, 'journal_entry_id');
    }

    public function party(): MorphTo
    {
        return $this->morphTo();
    }
}
