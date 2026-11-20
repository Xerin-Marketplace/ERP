<?php

namespace Modules\Reception\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\Reception\Services\ReceptionService;

class ReceptionController extends Controller
{
    public function index(ReceptionService $svc)
    {
        $user = auth()->user();
        $branchId = $svc->scopeBranch($user, request('branch_id') ? (int) request('branch_id') : null);

        return view('reception::dashboard', [
            'kpis' => $svc->kpis($branchId),
            'trend' => $svc->attendanceTrend($branchId),
            'departments' => $svc->departmentBreakdown($branchId),
            'attendance' => $svc->todayAttendance($branchId)->take(15),
            'tickets' => $svc->branchTickets($branchId, ['status' => null])->setPageName('t'),
            'technicians' => $svc->technicians($branchId),
            'branchId' => $branchId,
        ]);
    }

    public function technicians(ReceptionService $svc)
    {
        $branchId = $svc->scopeBranch(auth()->user());

        return view('reception::technicians', [
            'technicians' => $svc->technicians($branchId),
        ]);
    }
}
