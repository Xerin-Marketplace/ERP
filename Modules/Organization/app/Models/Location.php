<?php

namespace Modules\Organization\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Organization\Enums\EntityStatus;

class Location extends Model
{
    protected $fillable = [
        'company_id', 'branch_id', 'name', 'code', 'location_type',
        'country', 'region', 'district', 'city', 'physical_address',
        'latitude', 'longitude', 'status', 'created_by',
    ];

    public const TYPES = ['head_office', 'regional_office', 'branch_office', 'warehouse', 'site', 'other'];

    protected function casts(): array
    {
        return [
            'status' => EntityStatus::class,
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
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
