<?php

namespace Modules\HR\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\HR\Models\PayrollPeriod;
use Modules\HR\Models\PayrollRun;
use Modules\HR\Services\PayrollService;

class PayrollController extends Controller
{
    public function __construct(private PayrollService $payroll) {}

    public function index(Request $request)
    {
        abort_unless($request->user()->can('hr.payroll.view'), 403);

        return view('hr::payroll.index', [
            'periods' => PayrollPeriod::withCount('runs')->latest('start_date')->paginate(15),
            'runs' => PayrollRun::with('employee.personal', 'period')
                ->when($request->period_id, fn ($q, $id) => $q->where('payroll_period_id', $id))
                ->latest()->limit(50)->get(),
        ]);
    }

    public function storePeriod(Request $request)
    {
        abort_unless($request->user()->can('hr.payroll.process'), 403);

        $data = $request->validate([
            'name' => 'required|string|max:40',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'branch_id' => 'nullable|exists:branches,id',
        ]);

        $this->payroll->openPeriod($data, $request->user());
        return back()->with('success', "Payroll period {$data['name']} opened.");
    }

    public function process(Request $request, PayrollPeriod $period)
    {
        abort_unless($request->user()->can('hr.payroll.process'), 403);
        $count = $this->payroll->process($period, $request->user());
        return back()->with('success', "{$count} payroll runs generated.");
    }

    public function approve(Request $request, PayrollPeriod $period)
    {
        abort_unless($request->user()->can('hr.payroll.approve'), 403);
        $count = $this->payroll->approve($period, $request->user());
        return back()->with('success', "{$count} runs approved.");
    }

    public function pay(Request $request, PayrollRun $run)
    {
        abort_unless($request->user()->can('hr.payroll.approve'), 403);
        $this->payroll->markPaid($run, $request->user());
        return back()->with('success', "Run {$run->reference} marked paid.");
    }
}
