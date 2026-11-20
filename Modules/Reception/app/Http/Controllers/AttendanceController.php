<?php

namespace Modules\Reception\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\Organization\Models\Department;
use Modules\Reception\Services\ReceptionService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttendanceController extends Controller
{
    public function index(ReceptionService $svc)
    {
        $user = auth()->user();
        $branchId = $svc->scopeBranch($user, request('branch_id') ? (int) request('branch_id') : null);
        $date = request('date', today()->toDateString());
        $departmentId = request('department_id') ? (int) request('department_id') : null;

        return view('reception::attendance.index', [
            'records' => $svc->todayAttendance($branchId, $departmentId, $date),
            'departments' => Department::when($branchId, fn ($q) => $q->where('branch_id', $branchId))->orderBy('name')->get(['id', 'name']),
            'branchId' => $branchId,
            'date' => $date,
        ]);
    }

    public function export(ReceptionService $svc): StreamedResponse
    {
        $branchId = $svc->scopeBranch(auth()->user());
        $date = request('date', today()->toDateString());
        $records = $svc->todayAttendance($branchId, null, $date);

        return response()->streamDownload(function () use ($records) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Employee', 'Number', 'Department', 'Clock In', 'Clock Out', 'Status', 'Late (min)']);
            foreach ($records as $r) {
                fputcsv($out, [
                    $r->employee?->full_name, $r->employee?->employee_number,
                    $r->employee?->department?->name, $r->clock_in_at, $r->clock_out_at,
                    $r->status, $r->late_minutes,
                ]);
            }
            fclose($out);
        }, "attendance-{$date}.csv", ['Content-Type' => 'text/csv']);
    }
}
