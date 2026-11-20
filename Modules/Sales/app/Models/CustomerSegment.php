<?php
namespace Modules\Sales\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerSegment extends Model
{
    protected $fillable = ['name', 'code', 'rules', 'active'];
    protected $casts = ['rules' => 'array', 'active' => 'boolean'];
}
