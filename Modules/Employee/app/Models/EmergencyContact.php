<?php

namespace Modules\Employee\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmergencyContact extends Model
{
    protected $table = 'employee_emergency_contacts';
    protected $guarded = ['id'];

    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
}
