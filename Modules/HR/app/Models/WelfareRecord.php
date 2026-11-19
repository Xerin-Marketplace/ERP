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

class WelfareRecord extends Model
{
    protected $table = 'welfare_records';
    protected $guarded = ['id', 'reference'];
    protected function casts(): array
    {
        return ['recorded_on' => 'date', 'value decimal:2'];
    }

    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
}
