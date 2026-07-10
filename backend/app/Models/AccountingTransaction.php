<?php

namespace App\Models;

use App\Traits\BelongsToOffice;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountingTransaction extends Model
{
    use BelongsToOffice;

    protected $fillable = [
        'office_id', 'created_by', 'property_id', 'type', 'category',
        'amount', 'title', 'description', 'transaction_date', 'reference',
    ];

    protected function casts(): array
    {
        return ['transaction_date' => 'date'];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }
}
