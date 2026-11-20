<?php

namespace Modules\HR\Services;

use App\Models\User;
use App\Services\AuditService;
use Illuminate\Support\Facades\DB;
use Modules\Employee\Models\Employee;
use Modules\HR\Models\PayrollPeriod;
use Modules\HR\Models\PayrollRun;

class PayrollService
{
    public function __construct(
        private HRNumberService $numbers,
        private CompensationService $compensation,
        private AuditService $audit,
    ) {}

    public function openPeriod(array $data, User $actor): PayrollPeriod
    {
        $period = PayrollPeriod::create($data + ['status' => 'open']);
        $this->audit->log('hr.payroll.period_opened', $period, new: $data, metadata: ['actor' => $actor->id]);
        return $period;
    }

    /** Generate draft runs for every active employee with approved compensation. */
    public function process(PayrollPeriod $period, User $actor): int
    {
        abort_if(! in_array($period->status, ['open', 'processing']), 422, 'Period is not open for processing.');

        return DB::transaction(function () use ($period, $actor) {
            $employees = Employee::where('status', 'active')
                ->when($period->branch_id, fn ($q) => $q->where('branch_id', $period->branch_id))
                ->get();

            $count = 0;
            foreach ($employees as $employee) {
                $comp = $this->compensation->current($employee);
                if (! $comp) continue;

                $run = PayrollRun::updateOrCreate(
                    ['payroll_period_id' => $period->id, 'employee_id' => $employee->id],
                    [
                        'reference' => $this->numbers->for('payroll'),
                        'gross_amount' => $comp->gross(),
                        'total_deductions' => $comp->deductions(),
                        'net_amount' => $comp->net(),
                        'currency' => $comp->currency,
                        'status' => 'draft',
                        'breakdown' => $comp->only([
                            'basic_salary', 'housing_allowance', 'transport_allowance',
                            'medical_allowance', 'communication_allowance', 'meal_allowance',
                            'other_allowances', 'pension_deduction', 'insurance_deduction',
                            'tax_deduction', 'other_deductions', 'currency', 'pay_frequency',
                        ]),
                        'processed_by' => $actor->id,
                    ]
                );
                $count += $run->wasRecentlyCreated ? 1 : 0;
            }

            $period->update(['status' => 'processing']);
            $this->audit->log('hr.payroll.processed', $period,
                new: ['runs' => $count], metadata: ['actor' => $actor->id]);

            return $count;
        });
    }

    /** Approve all drafts in a period (multi-level gate). */
    public function approve(PayrollPeriod $period, User $actor): int
    {
        abort_if($period->status !== 'processing', 422, 'Nothing to approve.');
        abort_if($period->runs()->where('processed_by', $actor->id)->exists()
            && ! $actor->hasRole('super-admin'), 422, 'Processor cannot approve own payroll run.');

        $count = 0;
        DB::transaction(function () use ($period, $actor, &$count) {
            $count = $period->runs()->where('status', 'draft')->update([
                'status' => 'approved', 'approved_by' => $actor->id, 'approved_at' => now(),
            ]);
            $period->update(['status' => 'approved']);
            $this->audit->log('hr.payroll.approved', $period,
                new: ['runs_approved' => $count], metadata: ['actor' => $actor->id]);
        });

        return $count;
    }

    /** Mark a run as paid — irreversible except via reversal workflow. */
    public function markPaid(PayrollRun $run, User $actor): PayrollRun
    {
        abort_if($run->status !== 'approved', 422, 'Only approved runs can be marked paid.');
        $run->update(['status' => 'paid', 'paid_at' => now()]);
        $this->audit->log('hr.payroll.paid', $run,
            new: ['net' => $run->net_amount, 'ref' => $run->reference], metadata: ['actor' => $actor->id]);
        return $run;
    }

    /** Auditable reversal — never delete a finalized run. */
    public function reverse(PayrollRun $run, User $actor, string $reason): PayrollRun
    {
        abort_if(! in_array($run->status, ['approved', 'paid']), 422, 'Run cannot be reversed.');
        $run->update(['status' => 'reversed']);
        $this->audit->log('hr.payroll.reversed', $run,
            metadata: ['actor' => $actor->id, 'reason' => $reason]);
        return $run;
    }
}
