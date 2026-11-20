<?php

namespace Modules\SystemAdministration\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CommandExecution extends Model
{
    protected $table = 'system_command_executions';

    protected $fillable = ['command_id','user_id','params','risk','status','output','error',
        'duration_ms','reason','ip_address','correlation_id','started_at','finished_at'];

    protected function casts(): array
    {
        return ['params' => 'array', 'started_at' => 'datetime', 'finished_at' => 'datetime'];
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }

    public function approval(): HasOne
    {
        return $this->hasOne(CommandApproval::class, 'execution_id');
    }
}
