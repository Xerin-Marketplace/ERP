<?php

namespace Modules\HR\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Employee\Models\Employee;
use Modules\HR\Models\PerformanceCycle;
use Modules\HR\Services\PerformanceService;

class PerformanceController extends Controller
{
    public function __construct(private PerformanceService $svc) {}

    public function index(Request $request)
    {
        $cycles = PerformanceCycle::withCount(['objectives', 'reviews'])->latest()->get();
        $reviews = $this->svc->query($request->user())->paginate(15);
        $employees = $request->user()->can('performance.assess') || $request->user()->can('performance.manage')
            ? Employee::where('status', 'active')->orderBy('first_name')->get(['id', 'first_name', 'last_name', 'employee_number'])
            : collect();

        return view('hr::performance.index', compact('cycles', 'reviews', 'employees'));
    }

    public function storeCycle(Request $request)
    {
        abort_unless($request->user()->can('performance.manage'), 403);
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'starts_at' => 'required|date',
            'ends_at' => 'required|date|after:starts_at',
            'company_id' => 'nullable|exists:companies,id',
        ]);
        $this->svc->createCycle($data);
        return back()->with('status', 'Cycle created.');
    }

    public function storeObjective(Request $request, PerformanceCycle $cycle)
    {
        abort_unless($request->user()->can('performance.assess') || $request->user()->can('performance.manage'), 403);
        $data = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:3000',
            'weight' => 'nullable|numeric|min:0|max:100',
        ]);
        $this->svc->setObjective($cycle, Employee::findOrFail($data['employee_id']), $data);
        return back()->with('status', 'Objective set.');
    }

    public function storeReview(Request $request, PerformanceCycle $cycle)
    {
        $data = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'kind' => 'required|in:self,manager,peer',
            'rating' => 'nullable|integer|min:1|max:5',
            'summary' => 'nullable|string|max:5000',
            'improvement_plan' => 'nullable|string|max:5000',
        ]);
        $this->svc->review($cycle, Employee::findOrFail($data['employee_id']), $data, $request->user());
        return back()->with('status', 'Review submitted.');
    }
}
