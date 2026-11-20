<?php

namespace Modules\Arrival\Models;

use Illuminate\Database\Eloquent\Model;

class ReleaseApproval extends Model
{
    protected $table = 'release_approvals';

    protected $fillable = ['reference', 'package_id', 'invoice_id', 'branch_id', 'status', 'reason', 'release_code', 'decided_by', 'decided_at', 'code_used_at'];

    protected $casts = [
        'decided_at' => 'datetime',
        'code_used_at' => 'datetime',    ];

    public function package()
    {
        return $this->belongsTo(\Modules\Cargo\Models\CargoPackage::class, 'package_id');
    }

    public function invoice()
    {
        return $this->belongsTo(\Modules\Finance\Models\CustomerInvoice::class, 'invoice_id');
    }

    public function decider()
    {
        return $this->belongsTo(\App\Models\User::class, 'decided_by');
    }
}
