<?php

namespace Modules\Reception\Models;

use Illuminate\Database\Eloquent\Model;

class AccessWorkflowStage extends Model
{
    protected $table = 'access_workflow_stages';

    protected $fillable = ['version_id', 'sequence', 'name', 'approver_role', 'kind', 'sla_hours', 'required', 'conditions'];

    protected $casts = [
        'required' => 'boolean',
        'conditions' => 'array',
        'sla_hours' => 'integer',
        'sequence' => 'integer',    ];

    public function version()
    {
        return $this->belongsTo(\Modules\Reception\Models\AccessWorkflowVersion::class, 'version_id');
    }
}
