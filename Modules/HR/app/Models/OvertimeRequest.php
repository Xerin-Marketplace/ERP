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

class OvertimeRequest extends Model
{
    protected $table = 'overtime_requests';
    protected $guarded = ['id', 'reference'];
    protected function casts(): array
    {
        return ['work_date' => 'date', 'planned_hours decimal:2', 'actual_hours decimal:2', 'decided_at' => 'datetime'];
    }

    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
    public function decider(): BelongsTo { return $this->belongsTo(User::class, 'decided_by'); }
    public function approvals(): \Illuminate\Database\Eloquent\Relations\MorphMany { return $this->morphMany(HRApproval::class, 'approvable'); }
}
