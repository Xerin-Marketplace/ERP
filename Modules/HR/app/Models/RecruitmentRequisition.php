<?php

namespace Modules\HR\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Modules\Employee\Models\Employee;
use Modules\Organization\Models\{Branch, Company, Department};
use App\Models\User;
use App\Models\ManagedFile;

class RecruitmentRequisition extends Model
{
    protected $table = 'recruitment_requisitions';
    protected $guarded = ['id', 'reference'];
    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function branch(): BelongsTo { return $this->belongsTo(Branch::class); }
    public function department(): BelongsTo { return $this->belongsTo(Department::class); }
    public function requester(): BelongsTo { return $this->belongsTo(User::class, 'requested_by'); }
    public function vacancies(): HasMany { return $this->hasMany(JobVacancy::class, 'requisition_id'); }
    public function approvals(): \Illuminate\Database\Eloquent\Relations\MorphMany { return $this->morphMany(HRApproval::class, 'approvable'); }
}
