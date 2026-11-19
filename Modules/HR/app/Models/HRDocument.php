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

class HRDocument extends Model
{
    protected $table = 'hr_documents';
    protected $guarded = ['id', 'reference'];
    protected function casts(): array
    {
        return ['expires_at' => 'date', 'confidential' => 'boolean'];
    }

    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
    public function file(): BelongsTo { return $this->belongsTo(ManagedFile::class); }
    public function supersedes(): BelongsTo { return $this->belongsTo(HRDocument::class, 'supersedes_id'); }
}
