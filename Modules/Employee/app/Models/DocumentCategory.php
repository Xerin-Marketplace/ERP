<?php

namespace Modules\Employee\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentCategory extends Model
{
    protected $table = 'employee_document_categories';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['is_confidential' => 'boolean', 'requires_expiry' => 'boolean', 'is_active' => 'boolean'];
    }

    public function documents(): HasMany { return $this->hasMany(EmployeeDocument::class, 'category_id'); }
}
