<?php

namespace Modules\Reception\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Reception\Models\AccessWorkflow;
use Modules\Reception\Services\AccessWorkflowService;
use Spatie\Permission\Models\Role;

class WorkflowAdminController extends Controller
{
    public function __construct(private AccessWorkflowService $workflows) {}

    public function index()
    {
        return view('reception::access.workflows.index', [
            'workflows' => AccessWorkflow::with(['branch', 'department'])->get()
                ->map(fn ($w) => ['workflow' => $w,
                    'version' => \Modules\Reception\Models\AccessWorkflowVersion::where('workflow_id', $w->id)
                        ->where('version', $w->current_version)->with('stages')->first()]),
            'roles' => Role::orderBy('name')->pluck('name'),
        ]);
    }

    public function store(Request $r)
    {
        $data = $r->validate([
            'name' => 'required|string|max:255',
            'request_type' => 'required|in:new_employee,existing_employee,external,*',
            'branch_id' => 'nullable|exists:branches,id',
            'department_id' => 'nullable|exists:departments,id',
            'stages' => 'required|array|min:1',
            'stages.*.name' => 'required|string|max:120',
            'stages.*.approver_role' => 'required|string|exists:roles,name',
            'stages.*.sla_hours' => 'nullable|integer|min:1|max:720',
        ]);
        \DB::transaction(function () use ($data, $r) {
            $w = AccessWorkflow::create([
                'name' => $data['name'], 'request_type' => $data['request_type'],
                'branch_id' => $data['branch_id'] ?? null,
                'department_id' => $data['department_id'] ?? null,
                'current_version' => 0, 'created_by' => $r->user()->id,
            ]);
            $this->workflows->publishNewVersion($w, $data['stages'], $r->user()->id);
        });
        return back()->with('success', 'Workflow created.');
    }

    public function newVersion(Request $r, AccessWorkflow $workflow)
    {
        $data = $r->validate([
            'stages' => 'required|array|min:1',
            'stages.*.name' => 'required|string|max:120',
            'stages.*.approver_role' => 'required|string|exists:roles,name',
            'stages.*.sla_hours' => 'nullable|integer|min:1|max:720',
        ]);
        $v = $this->workflows->publishNewVersion($workflow, $data['stages'], $r->user()->id);
        return back()->with('success', "Workflow version {$v->version} published.");
    }

    public function toggle(AccessWorkflow $workflow)
    {
        $workflow->update(['is_active' => ! $workflow->is_active]);
        return back()->with('success', 'Workflow '.($workflow->is_active ? 'activated' : 'deactivated').'.');
    }
}
