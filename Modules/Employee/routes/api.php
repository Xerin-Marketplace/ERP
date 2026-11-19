<?php

use Illuminate\Support\Facades\Route;
use Modules\Employee\Http\Controllers\Api\V1\EmployeeApiController as Api;

Route::prefix('v1')->name('employee.')->middleware(['auth:sanctum', 'account.active'])->group(function () {

    Route::get('employees/dashboard', [Api::class, 'dashboard'])->name('dashboard');
    Route::get('employees', [Api::class, 'index'])->name('index');
    Route::post('employees', [Api::class, 'store'])->name('store');
    Route::get('employees/{employee}', [Api::class, 'show'])->name('show');
    Route::put('employees/{employee}', [Api::class, 'update'])->name('update');

    Route::get('employees/{employee}/documents', [Api::class, 'documents'])->name('documents.index');
    Route::post('employees/{employee}/documents', [Api::class, 'uploadDocument'])->name('documents.store');
    Route::get('employees/{employee}/history', [Api::class, 'history'])->name('history');
    Route::get('employees/{employee}/reporting', [Api::class, 'reporting'])->name('reporting');
    Route::post('employees/{employee}/transfer', [Api::class, 'transfer'])->name('transfer');
    Route::post('employees/{employee}/promotion', [Api::class, 'promotion'])->name('promotion');
    Route::post('employees/{employee}/offboarding', [Api::class, 'offboard'])->name('offboarding');

    Route::get('employee-positions', [Api::class, 'positions'])->name('positions');
    Route::get('employee-grades', [Api::class, 'grades'])->name('grades');
    Route::get('employee-document-categories', [Api::class, 'categories'])->name('categories');
});
