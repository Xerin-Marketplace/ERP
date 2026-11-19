<?php

namespace Modules\Organization\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DepartmentDefinition extends Model
{
    protected $fillable = ['code', 'name', 'description', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
    }
}
