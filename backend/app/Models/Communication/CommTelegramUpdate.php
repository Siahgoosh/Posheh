<?php

namespace App\Models\Communication;

use Illuminate\Database\Eloquent\Model;

class CommTelegramUpdate extends Model
{
    public $timestamps = false;

    protected $table = 'comm_telegram_updates';

    protected $fillable = ['update_id', 'payload', 'status'];

    protected function casts(): array
    {
        return ['payload' => 'array'];
    }
}
