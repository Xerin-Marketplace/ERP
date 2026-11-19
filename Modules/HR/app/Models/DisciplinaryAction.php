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

class DisciplinaryAction extends Model
{
    protected $table = 'disciplinary_actions';
    protected $guarded = ['id'];
    public function case_(): BelongsTo { return $this->belongsTo(DisciplinaryCase::class, 'case_id'); }
    public function file(): BelongsTo { return $this->belongsTo(ManagedFile::class); }
    public function actor(): BelongsTo { return $this->belongsTo(User::class); }
}
