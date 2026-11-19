<?php

namespace Modules\Employee\Models;

use App\Models\ManagedFile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeePersonalDetail extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array { return ['date_of_birth' => 'date']; }

    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
    public function photo(): BelongsTo { return $this->belongsTo(ManagedFile::class, 'photo_file_id'); }
}
