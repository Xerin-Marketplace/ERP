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

class AttendanceCorrection extends Model
{
    protected $table = 'attendance_corrections';
    protected $guarded = ['id', 'reference'];
    protected function casts(): array
    {
        return ['work_date' => 'date', 'decided_at' => 'datetime'];
    }

    public function attendance(): BelongsTo { return $this->belongsTo(AttendanceRecord::class); }
    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
    public function decider(): BelongsTo { return $this->belongsTo(User::class, 'decided_by'); }
}
