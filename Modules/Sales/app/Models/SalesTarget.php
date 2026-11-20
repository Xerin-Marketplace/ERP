<?php
namespace Modules\Sales\Models;

use Illuminate\Database\Eloquent\Model;

class SalesTarget extends Model
{
    protected $fillable = ['scope', 'scope_id', 'period_type', 'period', 'metric', 'target', 'currency'];
}
