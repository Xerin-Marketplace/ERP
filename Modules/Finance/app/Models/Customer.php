<?php

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $table = 'customers';

    protected $fillable = ['code','company_id','name','email','phone','tin','address','credit_limit','payment_terms_days','is_active'];

    protected $casts = [
        'is_active' => 'boolean',
        'credit_limit' => 'decimal:2',
    ];

    public function invoices()
    {
        return $this->hasMany(\Modules\Finance\Models\CustomerInvoice::class, 'customer_id');
    }

    public function company()
    {
        return $this->belongsTo(\Modules\Organization\Models\Company::class, 'company_id');
    }
}
