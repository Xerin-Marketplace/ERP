<?php

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentRecord extends Model
{
    protected $table = 'payment_records';

    protected $fillable = ['reference','company_id','direction','method','bank_account_id','payment_date','amount','payer_payee','external_ref','status','journal_id','created_by'];

    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function allocations()
    {
        return $this->hasMany(\Modules\Finance\Models\PaymentAllocation::class, 'payment_id');
    }

    public function journal()
    {
        return $this->belongsTo(\Modules\Finance\Models\Journal::class, 'journal_id');
    }

    public function company()
    {
        return $this->belongsTo(\Modules\Organization\Models\Company::class, 'company_id');
    }
}
