<?php

namespace Modules\Employee\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobGrade extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array { return ['is_active' => 'boolean']; }

    public function positions(): HasMany { return $this->hasMany(JobPosition::class); }
}
