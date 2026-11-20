<?php

namespace Modules\HR\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Employee\Models\Employee;

class EmployeeBankAccount extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'account_number' => 'encrypted',
            'iban' => 'encrypted',
            'passport_expiry' => 'date',
            'verified_at' => 'datetime',
            'is_primary' => 'boolean',
        ];
    }

    public function employee() { return $this->belongsTo(Employee::class); }
    public function verifier() { return $this->belongsTo(\App\Models\User::class, 'verified_by'); }

    /** Masked for ordinary interfaces — "****4521" */
    public function maskedAccount(): string
    {
        $n = $this->account_number ?? '';
        return strlen($n) > 4 ? '****'.substr($n, -4) : '****';
    }
}
