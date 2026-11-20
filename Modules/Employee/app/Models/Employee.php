<?php

namespace Modules\Employee\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Organization\Models\Branch;
use Modules\Organization\Models\Company;
use Modules\Organization\Models\Department;

class Employee extends Model
{
    use SoftDeletes;

    public const STATUS_ONBOARDING = 'onboarding';   // legacy — kept for existing rows
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PENDING = 'pending_approval';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_PROBATION = 'on_probation';
    public const STATUS_LEAVE = 'on_leave';
    public const STATUS_SUSPENDED = 'suspended';
    public const STATUS_RESIGNED = 'resigned';
    public const STATUS_TERMINATED = 'terminated';
    public const STATUS_SEPARATED = 'separated';     // legacy umbrella for resigned/terminated

    public const STATUSES = [
        self::STATUS_DRAFT, self::STATUS_PENDING, self::STATUS_ONBOARDING,
        self::STATUS_ACTIVE, self::STATUS_PROBATION, self::STATUS_LEAVE,
        self::STATUS_SUSPENDED, self::STATUS_RESIGNED, self::STATUS_TERMINATED,
        self::STATUS_SEPARATED,
    ];

    public const TYPES = ['permanent', 'contract', 'temporary', 'probation', 'intern', 'part_time', 'consultant'];

    protected $guarded = ['id', 'employee_number'];

    protected function casts(): array
    {
        return [
            'start_date' => 'date', 'confirmation_date' => 'date',
            'contract_end_date' => 'date', 'separation_date' => 'date',
        ];
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function personal(): HasOne { return $this->hasOne(EmployeePersonalDetail::class); }
    public function emergencyContacts(): HasMany { return $this->hasMany(EmergencyContact::class); }
    public function documents(): HasMany { return $this->hasMany(EmployeeDocument::class); }
    public function employmentRecords(): HasMany { return $this->hasMany(EmploymentRecord::class); }
    public function currentContract(): HasOne
    {
        return $this->hasOne(EmploymentRecord::class)->where('status', 'active')->latestOfMany('start_date');
    }
    public function events(): HasMany { return $this->hasMany(EmployeeEvent::class)->latest(); }
    public function bankAccounts(): HasMany { return $this->hasMany(\Modules\HR\Models\EmployeeBankAccount::class); }
    public function compensations(): HasMany { return $this->hasMany(\Modules\HR\Models\EmployeeCompensation::class); }
    public function payrollRuns(): HasMany { return $this->hasMany(\Modules\HR\Models\PayrollRun::class); }
    public function onboarding(): HasOne { return $this->hasOne(OnboardingRecord::class); }
    public function offboarding(): HasOne { return $this->hasOne(OffboardingRecord::class); }
    public function transfers(): HasMany { return $this->hasMany(TransferRequest::class); }
    public function changeRequests(): HasMany { return $this->hasMany(ChangeRequest::class); }
    public function reportingRelationships(): HasMany { return $this->hasMany(ReportingRelationship::class); }
    public function subordinates(): HasMany
    {
        return $this->hasMany(ReportingRelationship::class, 'manager_employee_id')->whereNull('effective_end');
    }

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function branch(): BelongsTo { return $this->belongsTo(Branch::class); }
    public function department(): BelongsTo { return $this->belongsTo(Department::class); }
    public function position(): BelongsTo { return $this->belongsTo(JobPosition::class, 'job_position_id'); }
    public function manager(): BelongsTo { return $this->belongsTo(Employee::class, 'manager_employee_id'); }

    public function getFullNameAttribute(): string
    {
        $p = $this->personal;
        return trim(collect([$p?->first_name, $p?->middle_name, $p?->last_name])->filter()->implode(' ')) ?: 'Employee #'.$this->id;
    }

    public function scopeSearch($q, ?string $term)
    {
        if (! $term) return $q;
        return $q->where(function ($w) use ($term) {
            $w->where('employee_number', 'like', "%{$term}%")
              ->orWhere('official_email', 'like', "%{$term}%")
              ->orWhereHas('personal', fn ($p) => $p
                  ->where('first_name', 'like', "%{$term}%")
                  ->orWhere('last_name', 'like', "%{$term}%")
                  ->orWhere('preferred_name', 'like', "%{$term}%"));
        });
    }

    public function scopeInCompany($q, ?int $companyId) { return $companyId ? $q->where('company_id', $companyId) : $q; }
    public function scopeInBranch($q, ?int $branchId) { return $branchId ? $q->where('branch_id', $branchId) : $q; }
    public function scopeInDepartment($q, ?int $deptId) { return $deptId ? $q->where('department_id', $deptId) : $q; }

    public function leaveBalances(): HasMany
    {
        return $this->hasMany(\Modules\HR\Models\LeaveBalance::class);
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(\Modules\HR\Models\AttendanceRecord::class);
    }
}
