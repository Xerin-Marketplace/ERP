<?php

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerInvoiceLine extends Model
{
    protected $table = 'customer_invoice_lines';

    protected $fillable = ['invoice_id','description','account_id','tax_rate_id','quantity','unit_price','discount','amount'];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'discount' => 'decimal:2',
        'amount' => 'decimal:2',
    ];

    public function invoice()
    {
        return $this->belongsTo(\Modules\Finance\Models\CustomerInvoice::class, 'invoice_id');
    }

    public function account()
    {
        return $this->belongsTo(\Modules\Finance\Models\ChartOfAccount::class, 'account_id');
    }

    public function taxRate()
    {
        return $this->belongsTo(\Modules\Finance\Models\TaxRate::class, 'tax_rate_id');
    }
}
