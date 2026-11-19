<?php

namespace Modules\Organization\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Organization\Enums\EntityStatus;

class Department extends Model
{
    protected $fillable = [
        'department_definition_id', 'code', 'name', 'description',
        'company_id', 'branch_id', 'parent_id', 'head_id', 'deputy_head_id',
        'email', 'phone', 'status', 'effective_date', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => EntityStatus::class,
            'effective_date' => 'date',
        ];
    }

    public function definition(): BelongsTo
    {
        return $this->belongsTo(DepartmentDefinition::class, 'department_definition_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Department::class, 'parent_id');
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
