<?php

namespace Modules\Reception\Models;

use Illuminate\Database\Eloquent\Model;

class AccessWorkflowVersion extends Model
{
    protected $table = 'access_workflow_versions';

    protected $fillable = ['workflow_id', 'version', 'status', 'effective_from'];

    protected $casts = [
        'effective_from' => 'date',    ];

    public function workflow()
    {
        return $this->belongsTo(\Modules\Reception\Models\AccessWorkflow::class, 'workflow_id');
    }

    public function stages()
    {
        return $this->hasMany(\Modules\Reception\Models\AccessWorkflowStage::class, 'version_id');
    }
}
