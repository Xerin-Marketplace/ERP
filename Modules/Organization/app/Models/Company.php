<?php

namespace Modules\Organization\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Modules\Organization\Enums\EntityStatus;

class Company extends Model
{
    protected $fillable = [
        'uuid', 'code', 'legal_name', 'trading_name', 'company_type',
        'parent_company_id', 'registration_number', 'tin', 'vat_number',
        'logo', 'email', 'phone', 'website', 'country', 'region', 'district',
        'city', 'physical_address', 'postal_address', 'currency', 'timezone',
        'operational_start_date', 'status', 'created_by',
    ];

    public const TYPES = ['holding', 'subsidiary', 'associate', 'entity'];

    protected function casts(): array
    {
        return [
            'status' => EntityStatus::class,
            'operational_start_date' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::creating(fn (Company $c) => $c->uuid ??= (string) Str::uuid());
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'parent_company_id');
    }

    public function subsidiaries(): HasMany
    {
        return $this->hasMany(Company::class, 'parent_company_id');
    }

    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }

    public function locations(): HasMany
    {
        return $this->hasMany(Location::class);
    }

    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
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

    /** IDs of this company plus all ancestors — used for circular checks */
    public function ancestorIds(): array
    {
        $ids = [];
        $node = $this->parent;
        while ($node) {
            $ids[] = $node->id;
            $node = $node->parent;
        }
        return $ids;
    }
}
