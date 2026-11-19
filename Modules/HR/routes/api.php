<?php

use Illuminate\Support\Facades\Route;
use Modules\HR\Http\Controllers\Api\V1\HRApiController as Api;

Route::prefix('v1/hr')->name('hr.')->middleware(['auth:sanctum', 'account.active'])->group(function () {

    Route::get('dashboard', [Api::class, 'dashboard'])->name('dashboard');

    Route::get('attendance', [Api::class, 'attendance'])->name('attendance.index');
    Route::post('attendance/clock-in', [Api::class, 'clockIn'])->name('attendance.clock-in');
    Route::post('attendance/clock-out', [Api::class, 'clockOut'])->name('attendance.clock-out');

    Route::get('leave', [Api::class, 'leave'])->name('leave.index');
    Route::post('leave/request', [Api::class, 'requestLeave'])->name('leave.request');
    Route::get('leave/balances', [Api::class, 'leaveBalances'])->name('leave.balances');
    Route::get('leave/types', [Api::class, 'leaveTypes'])->name('leave.types');
    Route::post('leave/{leaveRequest}/approve', fn (\Illuminate\Http\Request $r, \Modules\HR\Models\LeaveRequest $leaveRequest) => app(Api::class)->decideLeave($r->merge(['decision' => 'approved']), $leaveRequest))->name('leave.approve');
    Route::post('leave/{leaveRequest}/reject', fn (\Illuminate\Http\Request $r, \Modules\HR\Models\LeaveRequest $leaveRequest) => app(Api::class)->decideLeave($r->merge(['decision' => 'rejected']), $leaveRequest))->name('leave.reject');

    Route::get('shifts', [Api::class, 'shiftsIndex'])->name('shifts.index');
    Route::post('shifts', [Api::class, 'storeShift'])->name('shifts.store');

    Route::get('overtime', [Api::class, 'overtime'])->name('overtime.index');
    Route::post('overtime/request', [Api::class, 'requestOvertime'])->name('overtime.request');

    Route::get('recruitment/vacancies', [Api::class, 'vacancies'])->name('vacancies.index');

    Route::get('performance', fn () => \App\Support\ApiResponse::success(
        \Modules\HR\Models\PerformanceReview::latest()->paginate(15)))->name('performance.index')->middleware('can:performance.view');
});
