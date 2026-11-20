<?php

namespace Modules\Reception\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\Employee\Models\Employee;
use Modules\Reception\Services\ReceptionService;

class DirectoryController extends Controller
{
    public function index(ReceptionService $svc)
    {
        $branchId = $svc->scopeBranch(auth()->user(), request('branch_id') ? (int) request('branch_id') : null);

        // Directory exposes non-sensitive fields only — no salary/bank/ID data.
        $employees = Employee::query()
            ->with(['personal:id,employee_id,first_name,last_name,preferred_name', 'department:id,name', 'branch:id,name', 'position:id,title'])
            ->inBranch($branchId)
            ->where('status', '!=', 'terminated')
            ->search(request('q'))
            ->when(request('department_id'), fn ($q, $d) => $q->where('department_id', $d))
            ->orderBy('employee_number')
            ->paginate(25)
            ->withQueryString();

        return view('reception::directory.index', compact('employees', 'branchId'));
    }
}
