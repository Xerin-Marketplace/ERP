<?php

namespace Modules\Employee\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Organization\Models\Company;
use Modules\Organization\Models\Department;

class JobPosition extends Model
{
    protected $guarded = ['id'];

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function department(): BelongsTo { return $this->belongsTo(Department::class); }
    public function grade(): BelongsTo { return $this->belongsTo(JobGrade::class, 'job_grade_id'); }
    public function reportsTo(): BelongsTo { return $this->belongsTo(JobPosition::class, 'reports_to_position_id'); }
    public function employees(): HasMany { return $this->hasMany(Employee::class, 'job_position_id'); }
    public function scopeActive($q) { return $q->where('status', 'active'); }
}
