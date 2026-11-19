<?php

namespace Modules\Employee\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChangeRequest extends Model
{
    protected $table = 'employee_change_requests';
    protected $guarded = ['id'];

    public const TYPES = ['promotion', 'demotion', 'position_change', 'grade_change', 'type_change', 'confirmation', 'contract_renewal', 'salary_reference'];

    protected function casts(): array
    {
        return ['previous_values' => 'array', 'new_values' => 'array', 'effective_date' => 'date', 'reviewed_at' => 'datetime'];
    }

    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
    public function initiator(): BelongsTo { return $this->belongsTo(User::class, 'initiated_by'); }
    public function approver(): BelongsTo { return $this->belongsTo(User::class, 'approved_by'); }
    public function scopePending($q) { return $q->where('status', 'pending'); }
}
