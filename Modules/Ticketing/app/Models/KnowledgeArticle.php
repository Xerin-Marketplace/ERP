<?php

namespace Modules\Ticketing\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KnowledgeArticle extends Model
{
    protected $guarded = ['id'];

    public function category(): BelongsTo { return $this->belongsTo(KnowledgeCategory::class, 'category_id'); }
    public function author(): BelongsTo { return $this->belongsTo(User::class, 'author_id'); }
    public function scopePublished($q) { return $q->where('status', 'published'); }
}
