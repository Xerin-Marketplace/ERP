<?php

namespace Modules\Employee\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OnboardingRecord extends Model
{
    protected $table = 'employee_onboarding_records';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['started_at' => 'datetime', 'completed_at' => 'datetime'];
    }

    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
    public function starter(): BelongsTo { return $this->belongsTo(User::class, 'started_by'); }
    public function tasks(): HasMany { return $this->hasMany(OnboardingTask::class, 'record_id')->orderBy('sort_order'); }

    public function progress(): int
    {
        $total = $this->tasks()->count();
        if (! $total) return 0;
        return (int) round($this->tasks()->where('status', 'completed')->count() * 100 / $total);
    }
}
