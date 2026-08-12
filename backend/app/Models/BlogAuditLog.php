<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BlogAuditLog extends Model
{
    protected $fillable = [
        'blog_post_id', 'user_id', 'action', 'old_value', 'new_value', 'ip',
    ];

    protected function casts(): array
    {
        return [
            'old_value' => 'array',
            'new_value' => 'array',
        ];
    }
}
