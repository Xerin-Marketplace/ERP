<?php

namespace Modules\SystemAdministration\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommandApproval extends Model
{
    protected $table = 'system_command_approvals';

    protected $fillable = ['execution_id','decided_by','decision','comment','decided_at'];

    protected function casts(): array { return ['decided_at' => 'datetime']; }

    public function execution(): BelongsTo { return $this->belongsTo(CommandExecution::class); }
    public function decider(): BelongsTo { return $this->belongsTo(User::class, 'decided_by'); }
}
