<?php

namespace App\Models\Communication;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CommKnowledgeCategory extends Model
{
    protected $table = 'comm_knowledge_categories';

    protected $fillable = ['office_id', 'name', 'slug', 'sort_order'];

    public function articles(): HasMany
    {
        return $this->hasMany(CommKnowledgeArticle::class, 'category_id');
    }
}
