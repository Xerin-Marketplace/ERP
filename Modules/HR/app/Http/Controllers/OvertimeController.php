<?php

namespace Modules\HR\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\HR\Models\OvertimeRequest;
use Modules\HR\Services\OvertimeService;

class OvertimeController extends Controller
{
    public function __construct(private OvertimeService $svc) {}

    public function index(Request $request)
    {
        $requests = $this->svc->query($request->user())
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->paginate(15)->withQueryString();
        return view('hr::overtime.index', compact('requests'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'work_date' => 'required|date',
            'planned_hours' => 'required|numeric|min:0.5|max:24',
            'planned_start' => 'nullable|date_format:H:i',
            'planned_end' => 'nullable|date_format:H:i',
            'reason' => 'required|string|max:2000',
        ]);
        $this->svc->request($request->user()->employee, $data);
        return back()->with('status', 'Overtime request submitted.');
    }

    public function decide(Request $request, OvertimeRequest $overtime)
    {
        $data = $request->validate(['decision' => 'required|in:approved,rejected', 'note' => 'nullable|string|max:2000']);
        $this->svc->decide($overtime, $request->user(), $data['decision'], $data['note'] ?? null);
        return back()->with('status', "Overtime {$data['decision']}.");
    }

    public function verify(Request $request, OvertimeRequest $overtime)
    {
        $data = $request->validate(['actual_hours' => 'required|numeric|min:0.5|max:24']);
        $this->svc->verify($overtime, $request->user(), (float) $data['actual_hours']);
        return back()->with('status', 'Overtime verified.');
    }
}
