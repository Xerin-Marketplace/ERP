<?php

namespace Modules\HR\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Employee\Models\Employee;

class EmployeeCompensation extends Model
{
    protected $table = 'employee_compensations';

    protected $guarded = ['id'];

    public const STATUSES = ['pending', 'approved', 'superseded'];

    protected function casts(): array
    {
        return [
            'effective_date' => 'date', 'approved_at' => 'datetime',
            'basic_salary' => 'decimal:2', 'housing_allowance' => 'decimal:2',
            'transport_allowance' => 'decimal:2', 'medical_allowance' => 'decimal:2',
            'communication_allowance' => 'decimal:2', 'meal_allowance' => 'decimal:2',
            'other_allowances' => 'decimal:2', 'overtime_rate' => 'decimal:2',
            'pension_deduction' => 'decimal:2', 'insurance_deduction' => 'decimal:2',
            'tax_deduction' => 'decimal:2', 'other_deductions' => 'decimal:2',
        ];
    }

    public function employee() { return $this->belongsTo(Employee::class); }
    public function requester() { return $this->belongsTo(\App\Models\User::class, 'requested_by'); }
    public function approver() { return $this->belongsTo(\App\Models\User::class, 'approved_by'); }
    public function supersedes() { return $this->belongsTo(self::class, 'supersedes_id'); }

    public function gross(): float
    {
        return (float) $this->basic_salary + (float) $this->housing_allowance
            + (float) $this->transport_allowance + (float) $this->medical_allowance
            + (float) $this->communication_allowance + (float) $this->meal_allowance
            + (float) $this->other_allowances;
    }

    public function deductions(): float
    {
        return (float) $this->pension_deduction + (float) $this->insurance_deduction
            + (float) $this->tax_deduction + (float) $this->other_deductions;
    }

    public function net(): float { return $this->gross() - $this->deductions(); }
}
