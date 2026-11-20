<?php

namespace Modules\SystemAdministration\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class SettingChangeRequest extends Model
{
    protected $table = 'system_setting_change_requests';
    protected $fillable = ['key','scope_type','scope_id','old_value','new_value','risk','status',
        'base_version','requester_id','reason','approver_id','approver_comment','decided_at','published_at'];
    protected function casts(): array { return ['decided_at'=>'datetime','published_at'=>'datetime']; }
    public function requester() { return $this->belongsTo(User::class, 'requester_id'); }
    public function approver() { return $this->belongsTo(User::class, 'approver_id'); }
}
