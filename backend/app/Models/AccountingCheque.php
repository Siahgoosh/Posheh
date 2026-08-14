<?php

namespace App\Models;

use App\Traits\BelongsToOffice;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AccountingCheque extends Model
{
    use BelongsToOffice, SoftDeletes;

    protected $fillable = [
        'office_id', 'direction', 'cheque_number', 'sayad_id', 'amount',
        'bank_name', 'branch_name', 'issuer_name', 'issue_date', 'due_date',
        'status', 'party_type', 'party_id', 'property_id', 'crm_deal_id',
        'cash_account_id', 'journal_entry_id', 'description', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'issue_date' => 'date',
            'due_date' => 'date',
        ];
    }

    public function party(): MorphTo
    {
        return $this->morphTo();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(AccountingJournalEntry::class, 'journal_entry_id');
    }
}
