<?php

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Model;

class SupplierInvoiceLine extends Model
{
    protected $table = 'supplier_invoice_lines';

    protected $fillable = ['invoice_id','description','account_id','branch_id','department_id','quantity','unit_price','amount'];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'amount' => 'decimal:2',
    ];

    public function invoice()
    {
        return $this->belongsTo(\Modules\Finance\Models\SupplierInvoice::class, 'invoice_id');
    }

    public function account()
    {
        return $this->belongsTo(\Modules\Finance\Models\ChartOfAccount::class, 'account_id');
    }
}
