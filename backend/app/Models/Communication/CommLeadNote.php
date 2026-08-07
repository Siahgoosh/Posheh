<?php

namespace App\Models\Communication;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommLeadNote extends Model
{
    protected $table = 'comm_lead_notes';

    protected $fillable = ['lead_id', 'user_id', 'body'];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(CommLead::class, 'lead_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
