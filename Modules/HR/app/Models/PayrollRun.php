<?php

namespace Modules\HR\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Employee\Models\Employee;

class PayrollRun extends Model
{
    public const STATUSES = ['draft', 'approved', 'paid', 'reversed'];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'gross_amount' => 'decimal:2', 'total_deductions' => 'decimal:2',
            'net_amount' => 'decimal:2', 'breakdown' => 'array',
            'approved_at' => 'datetime', 'paid_at' => 'datetime',
        ];
    }

    public function period() { return $this->belongsTo(PayrollPeriod::class, 'payroll_period_id'); }
    public function employee() { return $this->belongsTo(Employee::class); }
    public function processor() { return $this->belongsTo(\App\Models\User::class, 'processed_by'); }
    public function approver() { return $this->belongsTo(\App\Models\User::class, 'approved_by'); }
}
