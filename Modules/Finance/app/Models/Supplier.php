<?php

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    protected $table = 'suppliers';

    protected $fillable = ['code','company_id','name','email','phone','tin','address','payment_terms_days','is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function invoices()
    {
        return $this->hasMany(\Modules\Finance\Models\SupplierInvoice::class, 'supplier_id');
    }

    public function company()
    {
        return $this->belongsTo(\Modules\Organization\Models\Company::class, 'company_id');
    }
}
