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

class LeaveBalance extends Model
{
    protected $table = 'leave_balances';
    protected $guarded = ['id'];
    protected function casts(): array
    {
        return ['entitled decimal:1', 'carried_forward decimal:1', 'used decimal:1', 'pending decimal:1', 'year' => 'integer'];
    }

    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
    public function type(): BelongsTo { return $this->belongsTo(LeaveType::class, 'leave_type_id'); }
    public function transactions(): HasMany { return $this->hasMany(LeaveTransaction::class); }
    public function getAvailableAttribute() { return (float)$this->entitled + (float)$this->carried_forward - (float)$this->used - (float)$this->pending; }
}
