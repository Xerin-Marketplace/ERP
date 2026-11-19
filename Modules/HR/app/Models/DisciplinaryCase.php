<?php

namespace Modules\HR\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Modules\Employee\Models\Employee;
use Modules\Organization\Models\{Branch, Company, Department};
use App\Models\User;
use App\Models\ManagedFile;

class DisciplinaryCase extends Model
{
    protected $table = 'disciplinary_cases';
    protected $guarded = ['id', 'reference'];
    protected function casts(): array
    {
        return ['incident_date' => 'date'];
    }

    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
    public function opener(): BelongsTo { return $this->belongsTo(User::class, 'opened_by'); }
    public function investigator(): BelongsTo { return $this->belongsTo(User::class); }
    public function actions(): HasMany { return $this->hasMany(DisciplinaryAction::class, 'case_id'); }
}
