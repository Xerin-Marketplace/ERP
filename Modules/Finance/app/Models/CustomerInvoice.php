<?php

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerInvoice extends Model
{
    protected $table = 'customer_invoices';

    protected $fillable = ['reference','company_id','customer_id','type','invoice_date','due_date','currency','subtotal','tax_amount','total','received_amount','status','journal_id','created_by'];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'received_amount' => 'decimal:2',
    ];

    public function lines()
    {
        return $this->hasMany(\Modules\Finance\Models\CustomerInvoiceLine::class, 'invoice_id');
    }

    public function customer()
    {
        return $this->belongsTo(\Modules\Finance\Models\Customer::class, 'customer_id');
    }

    public function journal()
    {
        return $this->belongsTo(\Modules\Finance\Models\Journal::class, 'journal_id');
    }

    public function company()
    {
        return $this->belongsTo(\Modules\Organization\Models\Company::class, 'company_id');
    }
    public function approvals()
    {
        return $this->morphMany(FinanceApproval::class, 'approvable');
    }
}
