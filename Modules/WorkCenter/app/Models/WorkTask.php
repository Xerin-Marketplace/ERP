<?php
namespace Modules\WorkCenter\Models;
use Illuminate\Database\Eloquent\Model;
class WorkTask extends Model
{
    protected $table = 'work_tasks';
    protected $guarded = [];
    protected function casts(): array
    {
        return ['sections' => 'array', 'tags' => 'array', 'content' => 'array',
            'period_start' => 'date', 'period_end' => 'date', 'expires_at' => 'date',
            'submitted_at' => 'datetime', 'published_at' => 'datetime',
            'completed_at' => 'datetime', 'starts_at' => 'datetime', 'due_at' => 'datetime',
            'decided_at' => 'datetime', 'requires_review' => 'boolean', 'active' => 'boolean'];
    }
    public function updates() { return $this->hasMany(TaskProgressUpdate::class, 'task_id'); }
    public function reviews() { return $this->hasMany(TaskReview::class, 'task_id'); }
    public function assignee() { return $this->belongsTo(\App\Models\User::class, 'assignee_id'); }
    public function creator() { return $this->belongsTo(\App\Models\User::class, 'created_by'); }
}
