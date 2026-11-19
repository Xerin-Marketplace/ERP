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

class AttendanceRecord extends Model
{
    protected $table = 'attendance_records';
    protected $guarded = ['id', 'reference'];
    protected function casts(): array
    {
        return ['work_date' => 'date', 'clock_in_at' => 'datetime', 'clock_out_at' => 'datetime', 'worked_minutes' => 'integer', 'late_minutes' => 'integer', 'early_departure_minutes' => 'integer', 'overtime_minutes' => 'integer', 'break_minutes' => 'integer'];
    }

    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function branch(): BelongsTo { return $this->belongsTo(Branch::class); }
    public function department(): BelongsTo { return $this->belongsTo(Department::class); }
    public function shift(): BelongsTo { return $this->belongsTo(ShiftDefinition::class); }
    public function events(): HasMany { return $this->hasMany(AttendanceEvent::class, 'attendance_id'); }
    public function scopeActive($q) { return $q->where('status', 'active'); }
}
