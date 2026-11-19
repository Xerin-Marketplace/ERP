<?php

namespace Modules\HR\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\HR\Models\InterviewRecord;
use Modules\HR\Models\JobVacancy;
use Modules\HR\Models\RecruitmentApplication;
use Modules\HR\Models\RecruitmentRequisition;
use Modules\HR\Services\RecruitmentService;
use Modules\Organization\Models\Company;
use Modules\Organization\Models\Department;

class RecruitmentController extends Controller
{
    public function __construct(private RecruitmentService $svc) {}

    public function index()
    {
        $requisitions = RecruitmentRequisition::with(['company', 'department', 'approvals'])->latest()->paginate(10);
        $vacancies = JobVacancy::withCount('applications')->latest()->paginate(10);
        $applications = RecruitmentApplication::with('vacancy')->latest()->paginate(15);
        $companies = Company::where('status', 'active')->get(['id', 'legal_name']);
        $departments = Department::where('status', 'active')->get(['id', 'name', 'company_id']);

        return view('hr::recruitment.index', compact('requisitions', 'vacancies', 'applications', 'companies', 'departments'));
    }

    public function storeRequisition(Request $request)
    {
        abort_unless($request->user()->can('recruitment.manage'), 403);
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'company_id' => 'required|exists:companies,id',
            'branch_id' => 'nullable|exists:branches,id',
            'department_id' => 'nullable|exists:departments,id',
            'positions' => 'nullable|integer|min:1|max:500',
            'employment_type' => 'nullable|string|max:30',
            'justification' => 'nullable|string|max:3000',
        ]);
        $this->svc->requisition($data, $request->user());
        return back()->with('status', 'Requisition submitted for approval.');
    }

    public function decideRequisition(Request $request, RecruitmentRequisition $requisition)
    {
        $data = $request->validate(['decision' => 'required|in:approved,rejected', 'note' => 'nullable|string|max:2000']);
        $this->svc->decideRequisition($requisition, $request->user(), $data['decision'], $data['note'] ?? null);
        return back()->with('status', "Requisition {$data['decision']}.");
    }

    public function storeVacancy(Request $request)
    {
        abort_unless($request->user()->can('recruitment.manage'), 403);
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:10000',
            'requirements' => 'nullable|string|max:10000',
            'department_id' => 'nullable|exists:departments,id',
            'requisition_id' => 'nullable|exists:recruitment_requisitions,id',
            'location' => 'nullable|string|max:255',
            'employment_type' => 'nullable|string|max:30',
            'positions' => 'nullable|integer|min:1|max:500',
            'closes_at' => 'nullable|date',
        ]);
        $req = isset($data['requisition_id']) ? RecruitmentRequisition::find($data['requisition_id']) : null;
        $this->svc->publishVacancy($data, $req);
        return back()->with('status', 'Vacancy published.');
    }

    public function storeApplication(Request $request, JobVacancy $vacancy)
    {
        abort_unless($request->user()->can('recruitment.manage'), 403);
        $data = $request->validate([
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:30',
            'cover_note' => 'nullable|string|max:5000',
        ]);
        $this->svc->apply($vacancy, $data);
        return back()->with('status', 'Application registered.');
    }

    public function moveStage(Request $request, RecruitmentApplication $application)
    {
        $data = $request->validate(['stage' => 'required|in:' . implode(',', RecruitmentService::STAGES), 'note' => 'nullable|string|max:2000']);
        $this->svc->moveStage($application, $data['stage'], $request->user(), $data['note'] ?? null);
        return back()->with('status', "Stage → {$data['stage']}.");
    }

    public function scheduleInterview(Request $request, RecruitmentApplication $application)
    {
        $data = $request->validate([
            'scheduled_at' => 'required|date',
            'mode' => 'required|in:in_person,phone,video',
            'location' => 'nullable|string|max:255',
        ]);
        $this->svc->scheduleInterview($application, $data, $request->user());
        return back()->with('status', 'Interview scheduled.');
    }

    public function assessInterview(Request $request, InterviewRecord $interview)
    {
        $data = $request->validate([
            'score' => 'required|integer|min:1|max:10',
            'assessment' => 'required|string|max:5000',
            'outcome' => 'required|in:pass,fail',
        ]);
        $this->svc->recordAssessment($interview, (int) $data['score'], $data['assessment'], $data['outcome'], $request->user());
        return back()->with('status', 'Assessment recorded.');
    }
}
