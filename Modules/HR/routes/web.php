<?php

use Illuminate\Support\Facades\Route;
use Modules\HR\Http\Controllers\AttendanceController;
use Modules\HR\Http\Controllers\DisciplinaryController;
use Modules\HR\Http\Controllers\HRDashboardController;
use Modules\HR\Http\Controllers\LeaveController;
use Modules\HR\Http\Controllers\OvertimeController;
use Modules\HR\Http\Controllers\PerformanceController;
use Modules\HR\Http\Controllers\RecruitmentController;
use Modules\HR\Http\Controllers\ShiftController;
use Modules\HR\Http\Controllers\TrainingController;
use Modules\HR\Http\Controllers\WelfareController;

Route::middleware(['auth', 'verified', 'account.active'])->prefix('hr')->name('hr.')->group(function () {

    Route::get('/', fn () => redirect()->route('hr.me'))->name('home');
    Route::get('dashboard', [HRDashboardController::class, 'index'])->name('dashboard');
    Route::get('me', [HRDashboardController::class, 'me'])->name('me');
    Route::get('reports', [HRDashboardController::class, 'reports'])->name('reports');

    // Attendance
    Route::get('attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::post('attendance/clock-in', [AttendanceController::class, 'clockIn'])->name('attendance.clock-in')->middleware('can:attendance.clock_in');
    Route::post('attendance/clock-out', [AttendanceController::class, 'clockOut'])->name('attendance.clock-out')->middleware('can:attendance.clock_out');
    Route::post('attendance/break-start', [AttendanceController::class, 'breakStart'])->name('attendance.break-start');
    Route::post('attendance/break-end', [AttendanceController::class, 'breakEnd'])->name('attendance.break-end');
    Route::post('attendance/corrections', [AttendanceController::class, 'requestCorrection'])->name('attendance.corrections.store');
    Route::post('attendance/corrections/{correction}/decide', [AttendanceController::class, 'decideCorrection'])->name('attendance.corrections.decide')->middleware('can:attendance.approve');

    // Leave
    Route::get('leave', [LeaveController::class, 'index'])->name('leave.index');
    Route::post('leave', [LeaveController::class, 'store'])->name('leave.store')->middleware('can:leave.request');
    Route::get('leave/approvals', [LeaveController::class, 'approvals'])->name('leave.approvals')->middleware('can:leave.approve');
    Route::post('leave/{leaveRequest}/decide', [LeaveController::class, 'decide'])->name('leave.decide')->middleware('can:leave.approve');
    Route::post('leave/{leaveRequest}/cancel', [LeaveController::class, 'cancel'])->name('leave.cancel');

    // Shifts
    Route::get('shifts', [ShiftController::class, 'index'])->name('shifts.index');
    Route::post('shifts', [ShiftController::class, 'store'])->name('shifts.store')->middleware('can:shift.create');
    Route::post('shifts/{shift}/assign', [ShiftController::class, 'assign'])->name('shifts.assign')->middleware('can:shift.assign');

    // Overtime
    Route::get('overtime', [OvertimeController::class, 'index'])->name('overtime.index');
    Route::post('overtime', [OvertimeController::class, 'store'])->name('overtime.store')->middleware('can:overtime.request');
    Route::post('overtime/{overtime}/decide', [OvertimeController::class, 'decide'])->name('overtime.decide')->middleware('can:overtime.approve');
    Route::post('overtime/{overtime}/verify', [OvertimeController::class, 'verify'])->name('overtime.verify')->middleware('can:overtime.manage');

    // Recruitment
    Route::get('recruitment', [RecruitmentController::class, 'index'])->name('recruitment.index')->middleware('can:recruitment.view');
    Route::post('recruitment/requisitions', [RecruitmentController::class, 'storeRequisition'])->name('recruitment.requisitions.store')->middleware('can:recruitment.manage');
    Route::post('recruitment/requisitions/{requisition}/decide', [RecruitmentController::class, 'decideRequisition'])->name('recruitment.requisitions.decide')->middleware('can:recruitment.approve');
    Route::post('recruitment/vacancies', [RecruitmentController::class, 'storeVacancy'])->name('recruitment.vacancies.store')->middleware('can:recruitment.manage');
    Route::post('recruitment/vacancies/{vacancy}/applications', [RecruitmentController::class, 'storeApplication'])->name('recruitment.applications.store')->middleware('can:recruitment.manage');
    Route::post('recruitment/applications/{application}/stage', [RecruitmentController::class, 'moveStage'])->name('recruitment.applications.stage')->middleware('can:recruitment.manage');
    Route::post('recruitment/applications/{application}/interview', [RecruitmentController::class, 'scheduleInterview'])->name('recruitment.interviews.store')->middleware('can:recruitment.manage');
    Route::post('recruitment/interviews/{interview}/assess', [RecruitmentController::class, 'assessInterview'])->name('recruitment.interviews.assess')->middleware('can:recruitment.manage');

    // Performance
    Route::get('performance', [PerformanceController::class, 'index'])->name('performance.index')->middleware('can:performance.view');
    Route::post('performance/cycles', [PerformanceController::class, 'storeCycle'])->name('performance.cycles.store')->middleware('can:performance.manage');
    Route::post('performance/cycles/{cycle}/objectives', [PerformanceController::class, 'storeObjective'])->name('performance.objectives.store')->middleware('can:performance.assess');
    Route::post('performance/cycles/{cycle}/reviews', [PerformanceController::class, 'storeReview'])->name('performance.reviews.store')->middleware('can:performance.view');

    // Training
    Route::get('training', [TrainingController::class, 'index'])->name('training.index')->middleware('can:training.view');
    Route::post('training/programs', [TrainingController::class, 'storeProgram'])->name('training.programs.store')->middleware('can:training.manage');
    Route::post('training/programs/{program}/enroll', [TrainingController::class, 'enroll'])->name('training.enroll');
    Route::post('training/enrollments/{enrollment}/complete', [TrainingController::class, 'complete'])->name('training.complete')->middleware('can:training.manage');

    // Disciplinary (confidential)
    Route::get('disciplinary', [DisciplinaryController::class, 'index'])->name('disciplinary.index')->middleware('can:disciplinary.view');
    Route::post('disciplinary', [DisciplinaryController::class, 'store'])->name('disciplinary.store')->middleware('can:disciplinary.manage');
    Route::get('disciplinary/{case}', [DisciplinaryController::class, 'show'])->name('disciplinary.show')->middleware('can:disciplinary.view');
    Route::post('disciplinary/{case}/action', [DisciplinaryController::class, 'action'])->name('disciplinary.action')->middleware('can:disciplinary.manage');
    Route::post('disciplinary/{case}/status', [DisciplinaryController::class, 'status'])->name('disciplinary.status')->middleware('can:disciplinary.manage');
    Route::post('disciplinary/{case}/response', [DisciplinaryController::class, 'response'])->name('disciplinary.response')->middleware('can:disciplinary.manage');

    // Welfare
    Route::get('welfare', [WelfareController::class, 'index'])->name('welfare.index')->middleware('can:welfare.view');
    Route::post('welfare', [WelfareController::class, 'store'])->name('welfare.store')->middleware('can:welfare.manage');
});
