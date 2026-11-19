<?php

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Model;

class BankTransaction extends Model
{
    protected $table = 'bank_transactions';

    protected $fillable = ['bank_account_id','txn_date','description','debit','credit','reference','source','matched_payment_id'];

    protected $casts = [
        'txn_date' => 'date',
        'debit' => 'decimal:2',
        'credit' => 'decimal:2',
    ];

    public function bankAccount()
    {
        return $this->belongsTo(\Modules\Finance\Models\BankAccount::class, 'bank_account_id');
    }

    public function matchedPayment()
    {
        return $this->belongsTo(\Modules\Finance\Models\PaymentRecord::class, 'matched_payment_id');
    }
}
