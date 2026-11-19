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
        'phone', 'email', 'country', 'region', 'district', 'city',
        'physical_address', 'postal_address', 'latitude', 'longitude',
        'opening_date', 'description', 'status', 'created_by',
    ];

    public const TYPES = ['head_office', 'regional_office', 'branch', 'warehouse', 'site'];

    protected function casts(): array
    {
        return [
            'status' => EntityStatus::class,
            'opening_date' => 'date',
            'latitude' => 'float',
            'longitude' => 'float',
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

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('status', EntityStatus::Active->value);
    }
}
