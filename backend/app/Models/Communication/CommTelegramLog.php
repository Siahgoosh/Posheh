<?php

namespace App\Models\Communication;

use Illuminate\Database\Eloquent\Model;

class CommTelegramLog extends Model
{
    public $timestamps = false;

    protected $table = 'comm_telegram_logs';

    protected $fillable = ['direction', 'method', 'payload', 'response', 'ok'];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'response' => 'array',
            'ok' => 'boolean',
            'created_at' => 'datetime',
        ];
    }
}
