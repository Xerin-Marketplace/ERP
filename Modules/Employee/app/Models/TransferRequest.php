<?php

namespace Modules\Employee\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Organization\Models\Branch;
use Modules\Organization\Models\Company;
use Modules\Organization\Models\Department;

class TransferRequest extends Model
{
    protected $table = 'employee_transfer_requests';
    protected $guarded = ['id'];

    public const TYPES = ['branch', 'department', 'company', 'position', 'manager', 'temporary'];

    protected function casts(): array
    {
        return ['effective_date' => 'date', 'reviewed_at' => 'datetime'];
    }

    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
    public function requester(): BelongsTo { return $this->belongsTo(User::class, 'requested_by'); }
    public function reviewer(): BelongsTo { return $this->belongsTo(User::class, 'reviewed_by'); }
    public function toCompany(): BelongsTo { return $this->belongsTo(Company::class, 'to_company_id'); }
    public function toBranch(): BelongsTo { return $this->belongsTo(Branch::class, 'to_branch_id'); }
    public function toDepartment(): BelongsTo { return $this->belongsTo(Department::class, 'to_department_id'); }
    public function toPosition(): BelongsTo { return $this->belongsTo(JobPosition::class, 'to_position_id'); }
    public function toManager(): BelongsTo { return $this->belongsTo(Employee::class, 'to_manager_employee_id'); }
    public function scopePending($q) { return $q->where('status', 'pending'); }
}
