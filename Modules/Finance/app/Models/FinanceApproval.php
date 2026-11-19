<?php

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Model;

class FinanceApproval extends Model
{
    protected $table = 'finance_approvals';

    protected $fillable = ['approvable_type','approvable_id','step','approver_role','approver_id','status','note','decided_at'];

    protected $casts = [
        'decided_at' => 'datetime',
    ];

    public function approvable()
    {
        return $this->morphTo();
    }

    public function approver()
    {
        return $this->belongsTo(\App\Models\User::class, 'approver_id');
    }
}
