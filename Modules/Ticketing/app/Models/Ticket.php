<?php

namespace Modules\Ticketing\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Employee\Models\Employee;
use Modules\Organization\Models\Department;

class Ticket extends Model
{
    public const STATUSES = ['new', 'submitted', 'under_review', 'assigned', 'in_progress', 'pending', 'resolved', 'closed', 'reopened'];
    public const PRIORITIES = ['low', 'normal', 'high', 'urgent', 'critical'];
    public const SEVERITIES = ['minor', 'moderate', 'major', 'critical'];

    /** Legal status transitions */
    public const TRANSITIONS = [
        'new' => ['submitted'],
        'submitted' => ['under_review', 'assigned'],
        'under_review' => ['assigned', 'pending', 'resolved', 'closed'],
        'assigned' => ['in_progress', 'pending', 'resolved'],
        'in_progress' => ['pending', 'resolved', 'assigned'],
        'pending' => ['in_progress', 'assigned', 'resolved'],
        'resolved' => ['closed', 'reopened'],
        'reopened' => ['assigned', 'in_progress', 'resolved'],
        'closed' => ['reopened'],
    ];

    protected $guarded = ['id', 'ticket_number'];

    protected function casts(): array
    {
        return [
            'due_at' => 'datetime', 'resolved_at' => 'datetime', 'closed_at' => 'datetime',
            'first_response_at' => 'datetime', 'custom_fields' => 'array',
        ];
    }

    public function requester(): BelongsTo { return $this->belongsTo(User::class, 'requester_id'); }
    public function requesterEmployee(): BelongsTo { return $this->belongsTo(Employee::class, 'requester_employee_id'); }
    public function agent(): BelongsTo { return $this->belongsTo(User::class, 'assigned_agent_id'); }
    public function team(): BelongsTo { return $this->belongsTo(TicketTeam::class, 'assigned_team_id'); }
    public function assignedDepartment(): BelongsTo { return $this->belongsTo(Department::class, 'assigned_department_id'); }
    public function type(): BelongsTo { return $this->belongsTo(TicketType::class, 'ticket_type_id'); }
    public function category(): BelongsTo { return $this->belongsTo(TicketCategory::class, 'category_id'); }
    public function service(): BelongsTo { return $this->belongsTo(ServiceCatalogItem::class, 'service_id'); }
    public function sla(): HasOne { return $this->hasOne(TicketSlaTracking::class); }
    public function comments(): HasMany { return $this->hasMany(TicketComment::class)->latest(); }
    public function publicComments(): HasMany { return $this->comments()->where('is_internal', false); }
    public function internalNotes(): HasMany { return $this->comments()->where('is_internal', true); }
    public function attachments(): HasMany { return $this->hasMany(TicketAttachment::class); }
    public function statusHistories(): HasMany { return $this->hasMany(TicketStatusHistory::class)->latest(); }
    public function assignmentHistory(): HasMany { return $this->hasMany(TicketAssignment::class)->latest(); }
    public function automationLogs(): HasMany { return $this->hasMany(TicketAutomationLog::class)->latest(); }
    public function approvals(): HasMany { return $this->hasMany(TicketApproval::class)->orderBy('step'); }
    public function escalations(): HasMany { return $this->hasMany(TicketEscalation::class)->latest(); }
    public function tasks(): HasMany { return $this->hasMany(TicketTask::class); }
    public function tags(): HasMany { return $this->hasMany(TicketTag::class); }

    public function canTransitionTo(string $to): bool
    {
        return in_array($to, self::TRANSITIONS[$this->status] ?? [], true);
    }

    public function isOpen(): bool
    {
        return ! in_array($this->status, ['resolved', 'closed'], true);
    }

    public function scopeSearch($q, ?string $term)
    {
        if (! $term) return $q;
        return $q->where(fn ($w) => $w->where('ticket_number', 'like', "%{$term}%")
            ->orWhere('subject', 'like', "%{$term}%"));
    }
}
