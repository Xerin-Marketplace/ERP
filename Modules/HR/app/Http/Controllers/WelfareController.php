<?php

namespace Modules\HR\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Employee\Models\Employee;
use Modules\HR\Services\WelfareService;

class WelfareController extends Controller
{
    public function __construct(private WelfareService $svc) {}

    public function index(Request $request)
    {
        $records = $this->svc->query($request->user())->paginate(15);
        $employees = $request->user()->can('welfare.manage')
            ? Employee::orderBy('first_name')->get(['id', 'first_name', 'last_name', 'employee_number'])
            : collect();
        return view('hr::welfare.index', compact('records', 'employees'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'type' => 'required|in:benefit,assistance,program,activity',
            'title' => 'required|string|max:255',
            'detail' => 'nullable|string|max:5000',
            'value' => 'nullable|numeric|min:0',
            'recorded_on' => 'required|date',
        ]);
        $this->svc->record(Employee::findOrFail($data['employee_id']), $data, $request->user());
        return back()->with('status', 'Welfare record saved.');
    }
}
