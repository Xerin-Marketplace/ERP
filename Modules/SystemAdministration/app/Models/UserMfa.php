<?php

namespace Modules\SystemAdministration\Models;

use Illuminate\Database\Eloquent\Model;

class UserMfa extends Model
{
    protected $table = 'user_mfa';
    protected $fillable = ['user_id','secret','recovery_hashes','enabled','verified_at'];
    protected function casts(): array
    {
        return ['secret' => 'encrypted', 'recovery_hashes' => 'array',
            'enabled' => 'boolean', 'verified_at' => 'datetime'];
    }
}
