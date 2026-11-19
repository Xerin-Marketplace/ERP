<?php

namespace Modules\Identity\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeAssignment extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'effective_start' => 'date',
            'effective_end' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(\Modules\Organization\Models\Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(\Modules\Organization\Models\Branch::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(\Modules\Organization\Models\Department::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'manager_user_id');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'assigned_by');
    }

    public function scopeCurrent($q)
    {
        return $q->whereIn('status', ['active', 'temporary'])->whereNull('effective_end');
    }
}
