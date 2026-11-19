<?php

namespace Modules\Employee\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportingRelationship extends Model
{
    protected $table = 'employee_reporting_relationships';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['effective_start' => 'date', 'effective_end' => 'date'];
    }

    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
    public function manager(): BelongsTo { return $this->belongsTo(Employee::class, 'manager_employee_id'); }
    public function scopeCurrent($q) { return $q->whereNull('effective_end'); }
}
