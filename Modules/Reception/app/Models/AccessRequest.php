<?php

namespace Modules\Reception\Models;

use Illuminate\Database\Eloquent\Model;

class AccessRequest extends Model
{
    protected $table = 'access_requests';

    protected $fillable = ['reference', 'type', 'status', 'full_name', 'email', 'phone', 'organization', 'company_id', 'branch_id', 'department_id', 'job_title', 'employment_type', 'manager_id', 'employee_id', 'sponsor_id', 'start_date', 'expiry_date', 'requested_modules', 'requested_changes', 'justification', 'tracking_token', 'workflow_version_id', 'current_stage_seq', 'priority', 'assigned_reviewer_id', 'provisioned_user_id', 'decision_reason', 'submitted_at', 'decided_at', 'provisioned_at'];

    protected $casts = [
        'requested_modules' => 'array',
        'requested_changes' => 'array',
        'start_date' => 'date',
        'expiry_date' => 'date',
        'submitted_at' => 'datetime',
        'decided_at' => 'datetime',
        'provisioned_at' => 'datetime',
        'current_stage_seq' => 'integer',    ];

    public function verifications()
    {
        return $this->hasMany(\Modules\Reception\Models\AccessVerification::class, 'request_id');
    }

    public function tasks()
    {
        return $this->hasMany(\Modules\Reception\Models\AccessApprovalTask::class, 'request_id');
    }

    public function history()
    {
        return $this->hasMany(\Modules\Reception\Models\AccessRequestHistory::class, 'request_id');
    }

    public function documents()
    {
        return $this->hasMany(\Modules\Reception\Models\AccessRequestDocument::class, 'request_id');
    }

    public function workflowVersion()
    {
        return $this->belongsTo(\Modules\Reception\Models\AccessWorkflowVersion::class, 'workflow_version_id');
    }

    public function branch()
    {
        return $this->belongsTo(\Modules\Organization\Models\Branch::class, 'branch_id');
    }

    public function department()
    {
        return $this->belongsTo(\Modules\Organization\Models\Department::class, 'department_id');
    }

    public function company()
    {
        return $this->belongsTo(\Modules\Organization\Models\Company::class, 'company_id');
    }

    public function employee()
    {
        return $this->belongsTo(\Modules\Employee\Models\Employee::class, 'employee_id');
    }

    public function provisionedUser()
    {
        return $this->belongsTo(\App\Models\User::class, 'provisioned_user_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(\App\Models\User::class, 'assigned_reviewer_id');
    }
}
