<?php

namespace Modules\Ticketing\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Employee\Models\Employee;

class TicketTask extends Model
{
    protected $table = 'ticket_tasks';
    public const STATUSES = ['open', 'assigned', 'in_progress', 'blocked', 'pending_review', 'completed', 'cancelled'];

    protected $guarded = ['id', 'task_number'];
    protected $casts = [
        'start_date' => 'date', 'due_date' => 'date', 'completed_at' => 'datetime',
    ];

    public function ticket(): BelongsTo { return $this->belongsTo(Ticket::class); }
    public function parent(): BelongsTo { return $this->belongsTo(TicketTask::class, 'parent_task_id'); }
    public function subtasks(): HasMany { return $this->hasMany(TicketTask::class, 'parent_task_id'); }
    public function assignee(): BelongsTo { return $this->belongsTo(Employee::class, 'assignee_employee_id'); }
    public function team(): BelongsTo { return $this->belongsTo(TicketTeam::class, 'team_id'); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function dependencies(): BelongsToMany
    {
        return $this->belongsToMany(TicketTask::class, 'ticket_task_dependencies', 'task_id', 'depends_on_id');
    }
    public function checklist(): HasMany { return $this->hasMany(TicketTaskChecklist::class, 'task_id')->orderBy('sort_order'); }
    public function comments(): HasMany { return $this->hasMany(TicketTaskComment::class, 'task_id')->latest(); }

    public function isBlocked(): bool
    {
        return $this->dependencies()->where('status', '!=', 'completed')->exists();
    }
}
