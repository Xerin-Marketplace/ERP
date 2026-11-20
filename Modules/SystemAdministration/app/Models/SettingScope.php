<?php

namespace Modules\SystemAdministration\Models;

use Illuminate\Database\Eloquent\Model;

class SettingScope extends Model
{
    protected $table = 'system_setting_scopes';
    protected $fillable = ['key','scope_type','scope_id','value','updated_by'];
}
