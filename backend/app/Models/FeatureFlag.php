<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeatureFlag extends Model
{
    protected $fillable = ['key', 'name', 'description', 'is_enabled', 'rules'];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'rules' => 'array',
        ];
    }
}
