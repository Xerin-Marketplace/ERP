<?php

namespace Modules\Employee\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OnboardingTask extends Model
{
    protected $table = 'employee_onboarding_tasks';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['is_required' => 'boolean', 'completed_at' => 'datetime', 'due_date' => 'date'];
    }

    public function record(): BelongsTo { return $this->belongsTo(OnboardingRecord::class, 'record_id'); }
    public function assignee(): BelongsTo { return $this->belongsTo(User::class, 'assigned_to'); }
    public function completer(): BelongsTo { return $this->belongsTo(User::class, 'completed_by'); }
}
