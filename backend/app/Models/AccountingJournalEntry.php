<?php

namespace App\Models;

use App\Traits\BelongsToOffice;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccountingJournalEntry extends Model
{
    use BelongsToOffice;

    protected $fillable = [
        'office_id', 'entry_number', 'entry_date', 'status', 'source_type',
        'source_id', 'description', 'created_by', 'approved_by', 'approved_at',
        'reversed_entry_id',
    ];

    protected function casts(): array
    {
        return [
            'entry_date' => 'date',
            'approved_at' => 'datetime',
        ];
    }

    public function lines(): HasMany
    {
        return $this->hasMany(AccountingJournalLine::class, 'journal_entry_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function totalDebit(): int
    {
        return (int) $this->lines()->sum('debit');
    }

    public function totalCredit(): int
    {
        return (int) $this->lines()->sum('credit');
    }

    public function isBalanced(): bool
    {
        if ($this->relationLoaded('lines')) {
            return (int) $this->lines->sum('debit') === (int) $this->lines->sum('credit')
                && (int) $this->lines->sum('debit') > 0;
        }

        return $this->totalDebit() === $this->totalCredit() && $this->totalDebit() > 0;
    }
}
