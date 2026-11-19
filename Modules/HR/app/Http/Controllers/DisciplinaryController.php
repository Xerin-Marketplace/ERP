<?php

namespace Modules\HR\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Employee\Models\Employee;
use Modules\HR\Models\DisciplinaryCase;
use Modules\HR\Services\DisciplinaryService;

class DisciplinaryController extends Controller
{
    public function __construct(private DisciplinaryService $svc) {}

    public function index(Request $request)
    {
        $cases = $this->svc->query($request->user())->paginate(15);
        $employees = $request->user()->can('disciplinary.manage')
            ? Employee::orderBy('first_name')->get(['id', 'first_name', 'last_name', 'employee_number'])
            : collect();
        return view('hr::disciplinary.index', compact('cases', 'employees'));
    }

    public function show(Request $request, DisciplinaryCase $case)
    {
        abort_unless($request->user()->can('disciplinary.view') || $request->user()->can('disciplinary.manage'), 403);
        $case->load(['employee.user', 'actions.actor', 'investigator']);
        return view('hr::disciplinary.show', compact('case'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'category' => 'required|string|max:40',
            'incident' => 'required|string|max:10000',
            'incident_date' => 'nullable|date',
            'severity' => 'required|in:minor,moderate,major,gross',
        ]);
        $this->svc->openCase(Employee::findOrFail($data['employee_id']), $data, $request->user());
        return back()->with('status', 'Case registered.');
    }

    public function action(Request $request, DisciplinaryCase $case)
    {
        $data = $request->validate([
            'type' => 'required|in:verbal_warning,written_warning,final_warning,suspension,termination,exonerated,appeal,note',
            'detail' => 'nullable|string|max:5000',
        ]);
        $this->svc->addAction($case, $data, $request->user());
        return back()->with('status', 'Action recorded.');
    }

    public function status(Request $request, DisciplinaryCase $case)
    {
        $data = $request->validate(['status' => 'required|in:' . implode(',', DisciplinaryService::STATUSES)]);
        $this->svc->advanceStatus($case, $data['status'], $request->user());
        return back()->with('status', "Status → {$data['status']}.");
    }

    public function response(Request $request, DisciplinaryCase $case)
    {
        $data = $request->validate(['employee_response' => 'required|string|max:10000']);
        $this->svc->recordResponse($case, $data['employee_response'], $request->user());
        return back()->with('status', 'Response recorded.');
    }
}
