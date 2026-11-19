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

class AttendanceEvent extends Model
{
    protected $table = 'attendance_events';
    protected $guarded = ['id'];
    protected function casts(): array
    {
        return ['occurred_at' => 'datetime', 'meta' => 'array'];
    }

    public function attendance(): BelongsTo { return $this->belongsTo(AttendanceRecord::class); }
    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
    public function recorder(): BelongsTo { return $this->belongsTo(User::class, 'recorded_by'); }
}
