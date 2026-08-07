<?php

namespace App\Models\Communication;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CommKnowledgeArticle extends Model
{
    use SoftDeletes;

    protected $table = 'comm_knowledge_articles';

    protected $fillable = [
        'category_id', 'office_id', 'title', 'slug', 'excerpt', 'body',
        'type', 'is_published', 'helpful_count', 'views', 'version', 'tags',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'tags' => 'array',
        ];
    }
}
