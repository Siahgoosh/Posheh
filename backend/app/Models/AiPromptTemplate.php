<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiPromptTemplate extends Model
{
    protected $fillable = [
        'office_id', 'key', 'name', 'version', 'template', 'variables',
        'provider', 'model', 'temperature', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'variables' => 'array',
            'temperature' => 'float',
            'is_active' => 'boolean',
        ];
    }
}
