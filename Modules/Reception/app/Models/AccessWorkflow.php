<?php

namespace Modules\Reception\Models;

use Illuminate\Database\Eloquent\Model;

class AccessWorkflow extends Model
{
    protected $table = 'access_workflows';

    protected $fillable = ['name', 'request_type', 'branch_id', 'department_id', 'current_version', 'is_active', 'created_by'];

    protected $casts = [
        'is_active' => 'boolean',    ];

    public function versions()
    {
        return $this->hasMany(\Modules\Reception\Models\AccessWorkflowVersion::class, 'workflow_id');
    }
}
