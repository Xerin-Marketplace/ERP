<?php

namespace Modules\Organization\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Organization\Enums\EntityStatus;

class Branch extends Model
{
    protected $fillable = [
        'company_id', 'name', 'code', 'branch_type', 'manager_id', 'contact_person',
        'phone', 'email', 'country', 'country_code', 'currency', 'timezone',
        'region', 'district', 'city',
        'physical_address', 'postal_address', 'postal_code', 'latitude', 'longitude',
        'opening_date', 'description', 'status', 'activated_at', 'deactivated_at',
        'created_by', 'updated_by',
    ];

    public const TYPES = ['head_office', 'regional_office', 'branch', 'warehouse', 'site'];

    protected function casts(): array
    {
        return [
            'status' => EntityStatus::class,
            'opening_date' => 'date',
            'latitude' => 'float',
            'longitude' => 'float',
            'activated_at' => 'datetime',
            'deactivated_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function locations(): HasMany
    {
        return $this->hasMany(Location::class);
    }

    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function settings(): HasMany
    {
        return $this->hasMany(BranchSetting::class);
    }

    public function administrators(): HasMany
    {
        return $this->hasMany(BranchAdministrator::class);
    }

    public function isActive(): bool
    {
        return $this->status === EntityStatus::Active;
    }

    /** Convert a UTC timestamp into this branch's local timezone. */
    public function toLocalTime(\DateTimeInterface $at): \DateTimeImmutable
    {
        return \Carbon\CarbonImmutable::instance($at)
            ->setTimezone($this->timezone ?: config('app.timezone'));
    }

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('status', EntityStatus::Active->value);
    }
}
