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

class TrainingCertification extends Model
{
    protected $table = 'training_certifications';
    protected $guarded = ['id'];
    protected function casts(): array
    {
        return ['issued_at' => 'date', 'expires_at' => 'date'];
    }

    public function enrollment(): BelongsTo { return $this->belongsTo(TrainingEnrollment::class); }
    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
    public function file(): BelongsTo { return $this->belongsTo(ManagedFile::class); }
}
