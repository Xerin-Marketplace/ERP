<?php

namespace Modules\SystemAdministration\Models;

use Illuminate\Database\Eloquent\Model;

class IntegrationTestResult extends Model
{
    protected $table = 'integration_test_results';
    protected $fillable = ['integration_id','status','duration_ms','error_code','message','tested_by'];
}
