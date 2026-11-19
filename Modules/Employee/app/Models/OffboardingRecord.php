<?php

namespace Modules\Employee\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OffboardingRecord extends Model
{
    protected $table = 'employee_offboarding_records';
    protected $guarded = ['id'];

    public const REASONS = ['resignation', 'termination', 'retirement', 'contract_expiry', 'death', 'other'];

    protected function casts(): array
    {
        return [
            'notice_date' => 'date', 'separation_date' => 'date', 'checklist' => 'array',
            'approved_at' => 'datetime', 'access_revoked_at' => 'datetime', 'completed_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
    public function requester(): BelongsTo { return $this->belongsTo(User::class, 'requested_by'); }
    public function approver(): BelongsTo { return $this->belongsTo(User::class, 'approved_by'); }
}
