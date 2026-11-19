<?php

namespace Modules\HR\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\HR\Models\HRAnnouncement;
use Modules\HR\Services\AttendanceService;
use Modules\HR\Services\HRDashboardService;

class HRDashboardController extends Controller
{
    public function __construct(
        private HRDashboardService $dash,
        private AttendanceService $attendance,
    ) {}

    public function index(Request $request)
    {
        abort_unless($request->user()->can('hr.dashboard.view'), 403);
        $metrics = $this->dash->for($request->user());
        $announcements = HRAnnouncement::where('published_at', '<=', now()->toDateString())
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>=', now()->toDateString()))
            ->latest('published_at')->limit(5)->get();
        return view('hr::dashboard', compact('metrics', 'announcements'));
    }

    /** Employee self-service portal. */
    public function me(Request $request)
    {
        $employee = $request->user()->employee;
        abort_unless($employee, 403, 'No employee record linked to this account.');

        $data = $this->dash->selfService($employee);
        $today = $this->attendance->recordFor($employee)->fresh();
        $announcements = HRAnnouncement::where('published_at', '<=', now()->toDateString())
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>=', now()->toDateString()))
            ->latest('published_at')->limit(5)->get();

        return view('hr::me', array_merge($data, compact('today', 'announcements', 'employee')));
    }

    public function reports(Request $request)
    {
        abort_unless($request->user()->can('attendance.reports.view') || $request->user()->can('hr.dashboard.view'), 403);
        $companyId = $request->user()->can('hr.dashboard.view') ? null : $request->user()->employee?->company_id;
        $report = $this->dash->report(
            $request->filled('from') ? \Carbon\Carbon::parse($request->from) : null,
            $request->filled('to') ? \Carbon\Carbon::parse($request->to) : null,
            $companyId
        );
        return view('hr::reports', compact('report'));
    }
}
