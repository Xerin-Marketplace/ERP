<?php

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Model;

class BankReconciliation extends Model
{
    protected $table = 'bank_reconciliations';

    protected $fillable = ['reference','bank_account_id','statement_date','statement_balance','book_balance','difference','status','prepared_by','approved_by'];

    protected $casts = [
        'statement_date' => 'date',
        'statement_balance' => 'decimal:2',
        'book_balance' => 'decimal:2',
        'difference' => 'decimal:2',
    ];

    public function bankAccount()
    {
        return $this->belongsTo(\Modules\Finance\Models\BankAccount::class, 'bank_account_id');
    }
}
