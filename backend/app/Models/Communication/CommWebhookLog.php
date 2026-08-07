<?php

namespace App\Models\Communication;

use Illuminate\Database\Eloquent\Model;

class CommWebhookLog extends Model
{
    public $timestamps = false;

    protected $table = 'comm_webhook_logs';

    protected $fillable = ['provider', 'event', 'payload', 'ok'];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'ok' => 'boolean',
            'created_at' => 'datetime',
        ];
    }
}
