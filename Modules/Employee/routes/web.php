<?php

use Illuminate\Support\Facades\Route;
use Modules\Employee\Http\Controllers\ChangeController;
use Modules\Employee\Http\Controllers\DocumentController;
use Modules\Employee\Http\Controllers\EmployeeController;
use Modules\Employee\Http\Controllers\LifecycleController;
use Modules\Employee\Http\Controllers\SelfServiceController;
use Modules\Employee\Http\Controllers\StructureController;
use Modules\Employee\Http\Controllers\TransferController;

Route::middleware(['auth', 'verified', 'account.active'])->prefix('employee')->name('employee.')->group(function () {

    Route::get('dashboard', [EmployeeController::class, 'dashboard'])->name('dashboard');
    Route::get('export', [EmployeeController::class, 'export'])->name('export');
    Route::get('me', [SelfServiceController::class, 'show'])->name('me');
    Route::put('me', [SelfServiceController::class, 'update'])->name('me.update');

    Route::get('/', [EmployeeController::class, 'index'])->name('index');
    Route::get('create', [EmployeeController::class, 'create'])->name('create');
    Route::post('/', [EmployeeController::class, 'store'])->name('store');
    Route::get('{employee}', [EmployeeController::class, 'show'])->name('show');
    Route::get('{employee}/edit', [EmployeeController::class, 'edit'])->name('edit');
    Route::put('{employee}', [EmployeeController::class, 'update'])->name('update');

    Route::get('{employee}/documents', [DocumentController::class, 'index'])->name('documents.index');
    Route::post('{employee}/documents', [DocumentController::class, 'store'])->name('documents.store');
    Route::get('documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download');
    Route::delete('documents/{document}', [DocumentController::class, 'destroy'])->name('documents.destroy');

    Route::get('structure/positions', [StructureController::class, 'index'])->name('structure.index');
    Route::post('structure/positions', [StructureController::class, 'storePosition'])->name('positions.store');
    Route::put('structure/positions/{position}', [StructureController::class, 'updatePosition'])->name('positions.update');
    Route::post('structure/grades', [StructureController::class, 'storeGrade'])->name('grades.store');
    Route::put('structure/grades/{grade}', [StructureController::class, 'updateGrade'])->name('grades.update');

    Route::get('transfers', [TransferController::class, 'index'])->name('transfers.index');
    Route::post('{employee}/transfers', [TransferController::class, 'store'])->name('transfers.store');
    Route::post('transfers/{transfer}/approve', [TransferController::class, 'approve'])->name('transfers.approve');
    Route::post('transfers/{transfer}/reject', [TransferController::class, 'reject'])->name('transfers.reject');

    Route::get('changes', [ChangeController::class, 'index'])->name('changes.index');
    Route::post('{employee}/changes', [ChangeController::class, 'store'])->name('changes.store');
    Route::post('changes/{change}/approve', [ChangeController::class, 'approve'])->name('changes.approve');
    Route::post('changes/{change}/reject', [ChangeController::class, 'reject'])->name('changes.reject');

    Route::get('onboarding', [LifecycleController::class, 'onboardingIndex'])->name('onboarding.index');
    Route::post('{employee}/onboarding', [LifecycleController::class, 'startOnboarding'])->name('onboarding.start');
    Route::post('onboarding/tasks/{task}', [LifecycleController::class, 'updateTask'])->name('onboarding.task');

    Route::get('offboarding', [LifecycleController::class, 'offboardingIndex'])->name('offboarding.index');
    Route::post('{employee}/offboarding', [LifecycleController::class, 'initiateOffboarding'])->name('offboarding.initiate');
    Route::post('offboarding/{record}/approve', [LifecycleController::class, 'approveOffboarding'])->name('offboarding.approve');
    Route::post('offboarding/{record}/complete', [LifecycleController::class, 'completeOffboarding'])->name('offboarding.complete');
});
