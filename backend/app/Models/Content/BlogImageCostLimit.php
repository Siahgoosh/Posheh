<?php

namespace App\Models\Content;

use Illuminate\Database\Eloquent\Model;

class BlogImageCostLimit extends Model
{
    protected $table = 'blog_image_cost_limits';

    protected $fillable = ['scope', 'limit_toman', 'limit_count', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
