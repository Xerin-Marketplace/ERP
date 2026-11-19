<?php

namespace Modules\Ticketing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Modules\Employee\Models\Employee;
use Modules\Organization\Models\Branch;
use Modules\Organization\Models\Department;

class TicketTeam extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['is_active' => 'boolean'];

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'ticket_team_members', 'team_id', 'employee_id')->withTimestamps();
    }
    public function leader(): BelongsTo { return $this->belongsTo(Employee::class, 'leader_employee_id'); }
    public function department(): BelongsTo { return $this->belongsTo(Department::class); }
    public function branch(): BelongsTo { return $this->belongsTo(Branch::class); }
    public function scopeActive($q) { return $q->where('is_active', true); }
}
