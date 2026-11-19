<?php

namespace Modules\Identity\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Identity\Enums\ApplicationStatus;

class RegistrationApplication extends Model
{
    use SoftDeletes;

    protected $guarded = ['id', 'uuid'];

    protected function casts(): array
    {
        return [
            'status' => ApplicationStatus::class,
            'date_of_birth' => 'date',
            'employment_start_date' => 'date',
            'submitted_at' => 'datetime',
            'decided_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $app) {
            $app->uuid ??= (string) \Illuminate\Support\Str::uuid();
        });
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->middle_name} {$this->last_name}");
    }

    /* ---------------------------- Relations ---------------------------- */

    public function documents(): HasMany
    {
        return $this->hasMany(RegistrationDocument::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(RegistrationStatusHistory::class)->latest();
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(RegistrationReview::class)->latest();
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'reviewed_by');
    }

    public function provisionedUser(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'provisioned_user_id');
    }

    public function requestedCompany(): BelongsTo
    {
        return $this->belongsTo(\Modules\Organization\Models\Company::class, 'requested_company_id');
    }

    public function requestedBranch(): BelongsTo
    {
        return $this->belongsTo(\Modules\Organization\Models\Branch::class, 'requested_branch_id');
    }

    public function requestedDepartment(): BelongsTo
    {
        return $this->belongsTo(\Modules\Organization\Models\Department::class, 'requested_department_id');
    }
}
