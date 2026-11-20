<?php
namespace Modules\WorkCenter\Models;
use Illuminate\Database\Eloquent\Model;
class TaskReview extends Model
{
    protected $table = 'task_reviews';
    protected $guarded = [];
    protected function casts(): array
    {
        return ['sections' => 'array', 'tags' => 'array', 'content' => 'array',
            'period_start' => 'date', 'period_end' => 'date', 'expires_at' => 'date',
            'submitted_at' => 'datetime', 'published_at' => 'datetime',
            'completed_at' => 'datetime', 'starts_at' => 'datetime', 'due_at' => 'datetime',
            'decided_at' => 'datetime', 'requires_review' => 'boolean', 'active' => 'boolean'];
    }
}
