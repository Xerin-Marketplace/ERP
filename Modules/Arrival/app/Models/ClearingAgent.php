<?php
namespace Modules\Arrival\Models;
use Illuminate\Database\Eloquent\Model;

class ClearingAgent extends Model
{
    protected $table = 'clearing_agents';
    protected $guarded = ['id', 'reference'];
    protected $casts = ['license_expiry' => 'date'];
}
