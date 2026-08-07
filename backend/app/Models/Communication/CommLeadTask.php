<?php

namespace App\Models\Communication;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommLeadTask extends Model
{
    protected $table = 'comm_lead_tasks';

    protected $fillable = [
        'lead_id', 'assigned_to', 'type', 'title', 'status', 'due_at',
    ];

    protected function casts(): array
    {
        return ['due_at' => 'datetime'];
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(CommLead::class, 'lead_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
