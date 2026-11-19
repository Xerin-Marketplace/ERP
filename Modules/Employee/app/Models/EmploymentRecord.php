<?php

namespace Modules\Employee\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmploymentRecord extends Model
{
    protected $table = 'employee_employment_records';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['start_date' => 'date', 'end_date' => 'date'];
    }

    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
    public function renewedFrom(): BelongsTo { return $this->belongsTo(EmploymentRecord::class, 'renewed_from_id'); }
}
