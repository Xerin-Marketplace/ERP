<?php

namespace Modules\Reception\Models;

use Illuminate\Database\Eloquent\Model;

class AccessApprovalTask extends Model
{
    protected $table = 'access_approval_tasks';

    protected $fillable = ['request_id', 'stage_id', 'sequence', 'approver_role', 'assignee_id', 'status', 'comment', 'decided_by', 'decided_at', 'due_at'];

    protected $casts = [
        'decided_at' => 'datetime',
        'due_at' => 'datetime',
        'sequence' => 'integer',    ];

    public function request()
    {
        return $this->belongsTo(\Modules\Reception\Models\AccessRequest::class, 'request_id');
    }

    public function stage()
    {
        return $this->belongsTo(\Modules\Reception\Models\AccessWorkflowStage::class, 'stage_id');
    }

    public function assignee()
    {
        return $this->belongsTo(\App\Models\User::class, 'assignee_id');
    }

    public function decider()
    {
        return $this->belongsTo(\App\Models\User::class, 'decided_by');
    }
}
