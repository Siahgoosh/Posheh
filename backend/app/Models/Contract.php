<?php

namespace App\Models;

use App\Traits\BelongsToOffice;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Contract extends Model
{
    use BelongsToOffice;

    protected $fillable = [
        'office_id', 'property_id', 'created_by', 'template_id',
        'title', 'content', 'field_values', 'party_a_name', 'party_b_name',
        'status', 'pdf_path', 'docx_path',
    ];

    protected function casts(): array
    {
        return ['field_values' => 'array'];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(ContractTemplate::class, 'template_id');
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
