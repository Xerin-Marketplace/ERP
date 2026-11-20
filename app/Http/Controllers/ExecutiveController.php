<?php

namespace App\Http\Controllers;

use App\Services\Executive\ExecutiveAnalyticsService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Organization\Models\Branch;
use Modules\Organization\Models\Company;

class ExecutiveController extends Controller
{
    public function __construct(private ExecutiveAnalyticsService $an) {}

    private function companyId(): int
    {
        return (int) Company::value('id');
    }

    /** Resolve global filters shared by every executive page. */
    private function filters(Request $r): array
    {
        [$from, $to, $pf, $pt] = $this->an->period(
            $r->get('range', 'month'), $r->get('from'), $r->get('to'));
        $branchId = $r->filled('branch') ? (int) $r->branch : null;
        return compact('from', 'to', 'pf', 'pt', 'branchId');
    }

    private function shared(Request $r, array $extra = []): array
    {
        $f = $this->filters($r);
        return $f + [
            'presets' => ExecutiveAnalyticsService::PRESETS,
            'range' => $r->get('range', 'month'),
            'branches' => Branch::orderBy('code')->get(['id', 'code', 'name']),
            'refreshedAt' => now(),
        ] + $extra;
    }

    public function dashboard(Request $r)
    {
        $f = $this->filters($r);
        return view('executive.dashboard', $this->shared($r, [
            'kpis' => $this->an->executive($this->companyId(), $f['from'], $f['to'], $f['pf'], $f['pt'], $f['branchId']),
            'trend' => $this->an->financeTrend($this->companyId(), $f['from'], $f['to'], $f['branchId']),
            'branchRows' => $this->an->branches($this->companyId(), $f['from'], $f['to']),
            'activity' => $this->an->activity(15),
        ]));
    }

    public function financial(Request $r)
    {
        $f = $this->filters($r);
        return view('executive.financial', $this->shared($r, [
            'kpis' => $this->an->executive($this->companyId(), $f['from'], $f['to'], $f['pf'], $f['pt'], $f['branchId']),
            'trend' => $this->an->financeTrend($this->companyId(), $f['from'], $f['to'], $f['branchId']),
            'metrics' => $this->an->financeMetrics($this->companyId(), $f['from'], $f['to']),
        ]));
    }

    public function branches(Request $r)
    {
        $f = $this->filters($r);
        return view('executive.branches', $this->shared($r, [
            'rows' => $this->an->branches($this->companyId(), $f['from'], $f['to']),
        ]));
    }

    public function branch(Request $r, Branch $branch)
    {
        $f = $this->filters($r);
        return view('executive.branch', $this->shared($r, [
            'branch' => $branch,
            'kpis' => $this->an->executive($this->companyId(), $f['from'], $f['to'], $f['pf'], $f['pt'], $branch->id),
            'trend' => $this->an->financeTrend($this->companyId(), $f['from'], $f['to'], $branch->id),
            'departments' => $this->an->departments($this->companyId(), $branch->id),
            'employees' => $this->an->employees($this->companyId(), $branch->id, null),
        ]));
    }

    public function departments(Request $r)
    {
        $f = $this->filters($r);
        return view('executive.departments', $this->shared($r, [
            'rows' => $this->an->departments($this->companyId(), $f['branchId']),
        ]));
    }

    public function sales(Request $r)
    {
        $f = $this->filters($r);
        return view('executive.sales', $this->shared($r, [
            'sales' => $this->an->sales($this->companyId(), $f['from'], $f['to'], $f['pf'], $f['pt'], $f['branchId']),
        ]));
    }

    public function employees(Request $r)
    {
        $f = $this->filters($r);
        return view('executive.employees', $this->shared($r, [
            'rows' => $this->an->employees($this->companyId(), $f['branchId'],
                $r->filled('department') ? (int) $r->department : null),
        ]));
    }

    public function tasks(Request $r)
    {
        $f = $this->filters($r);
        return view('executive.tasks', $this->shared($r, [
            'tasks' => $this->an->tasks($this->companyId(), $f['branchId'],
                $r->filled('department') ? (int) $r->department : null),
        ]));
    }

    public function tenders(Request $r)
    {
        $f = $this->filters($r);
        return view('executive.tenders', $this->shared($r, [
            'tenders' => $this->an->tenders($this->companyId(), $f['branchId']),
        ]));
    }

    public function activity(Request $r)
    {
        return view('executive.activity', $this->shared($r, [
            'activity' => $this->an->activity(100, $r->get('action')),
        ]));
    }
}
