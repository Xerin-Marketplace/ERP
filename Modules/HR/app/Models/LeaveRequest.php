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

class LeaveRequest extends Model
{
    protected $table = 'leave_requests';
    protected $guarded = ['id', 'reference'];
    protected function casts(): array
    {
        return ['start_date' => 'date', 'end_date' => 'date', 'days decimal:1', 'half_day' => 'boolean', 'decided_at' => 'datetime'];
    }

    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
    public function type(): BelongsTo { return $this->belongsTo(LeaveType::class, 'leave_type_id'); }
    public function decider(): BelongsTo { return $this->belongsTo(User::class, 'decided_by'); }
    public function attachment(): BelongsTo { return $this->belongsTo(ManagedFile::class, 'attachment_file_id'); }
    public function approvals(): \Illuminate\Database\Eloquent\Relations\MorphMany { return $this->morphMany(HRApproval::class, 'approvable'); }
}
