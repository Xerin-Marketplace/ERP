<?php

namespace App\Http\Controllers\Api;

use App\Services\Executive\ExecutiveAnalyticsService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Organization\Models\Company;

/**
 * Executive analytics API — same service as the web pages, JSON out.
 * Gated by the executive.command.view permission via route middleware.
 */
class ExecutiveApiController extends Controller
{
    public function __construct(private ExecutiveAnalyticsService $an) {}

    private function ctx(Request $r): array
    {
        [$from, $to, $pf, $pt] = $this->an->period(
            $r->get('range', 'month'), $r->get('from'), $r->get('to'));
        return [
            'company' => (int) Company::value('id'),
            'from' => $from, 'to' => $to, 'pf' => $pf, 'pt' => $pt,
            'branch' => $r->filled('branch') ? (int) $r->branch : null,
        ];
    }

    public function dashboard(Request $r)
    {
        $c = $this->ctx($r);
        return response()->json([
            'period' => ['from' => $c['from'], 'to' => $c['to']],
            'kpis' => $this->an->executive($c['company'], $c['from'], $c['to'], $c['pf'], $c['pt'], $c['branch']),
            'trend' => $this->an->financeTrend($c['company'], $c['from'], $c['to'], $c['branch']),
            'generated_at' => now()->toIso8601String(),
        ]);
    }

    public function financial(Request $r)
    {
        $c = $this->ctx($r);
        return response()->json([
            'period' => ['from' => $c['from'], 'to' => $c['to']],
            'kpis' => $this->an->executive($c['company'], $c['from'], $c['to'], $c['pf'], $c['pt'], $c['branch']),
            'finance' => $this->an->financeMetrics($c['company'], $c['from'], $c['to']),
            'trend' => $this->an->financeTrend($c['company'], $c['from'], $c['to'], $c['branch']),
        ]);
    }

    public function branches(Request $r)
    {
        $c = $this->ctx($r);
        return response()->json(['branches' => $this->an->branches($c['company'], $c['from'], $c['to'])->values()]);
    }

    public function departments(Request $r)
    {
        $c = $this->ctx($r);
        return response()->json(['departments' => $this->an->departments($c['company'], $c['branch'])->values()]);
    }

    public function sales(Request $r)
    {
        $c = $this->ctx($r);
        return response()->json(['period' => ['from' => $c['from'], 'to' => $c['to']]]
            + $this->an->sales($c['company'], $c['from'], $c['to'], $c['pf'], $c['pt'], $c['branch']));
    }

    public function employees(Request $r)
    {
        $c = $this->ctx($r);
        return response()->json(['employees' => $this->an->employees($c['company'], $c['branch'],
            $r->filled('department') ? (int) $r->department : null)->values()]);
    }

    public function tasks(Request $r)
    {
        $c = $this->ctx($r);
        return response()->json($this->an->tasks($c['company'], $c['branch'],
            $r->filled('department') ? (int) $r->department : null));
    }

    public function tenders(Request $r)
    {
        $c = $this->ctx($r);
        return response()->json($this->an->tenders($c['company'], $c['branch']));
    }

    public function activity(Request $r)
    {
        return response()->json(['activity' => $this->an->activity(
            min((int) $r->get('limit', 40), 200), $r->get('action'))->values()]);
    }
}
