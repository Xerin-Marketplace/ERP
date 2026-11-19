<?php

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Model;

class BankAccount extends Model
{
    protected $table = 'bank_accounts';

    protected $fillable = ['company_id','branch_id','account_id','name','bank_name','account_number','currency','opening_balance','is_active'];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function glAccount()
    {
        return $this->belongsTo(\Modules\Finance\Models\ChartOfAccount::class, 'account_id');
    }

    public function transactions()
    {
        return $this->hasMany(\Modules\Finance\Models\BankTransaction::class, 'bank_account_id');
    }

    public function company()
    {
        return $this->belongsTo(\Modules\Organization\Models\Company::class, 'company_id');
    }
}
