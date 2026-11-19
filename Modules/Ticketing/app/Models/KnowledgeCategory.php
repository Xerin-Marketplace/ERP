<?php

namespace Modules\Ticketing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KnowledgeCategory extends Model
{
    protected $guarded = ['id'];
    public function articles(): HasMany { return $this->hasMany(KnowledgeArticle::class, 'category_id'); }
}
