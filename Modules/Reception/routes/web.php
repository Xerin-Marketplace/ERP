<?php

use Illuminate\Support\Facades\Route;
use Modules\Reception\Http\Controllers\AttendanceController;
use Modules\Reception\Http\Controllers\DirectoryController;
use Modules\Reception\Http\Controllers\ReceptionController;
use Modules\Reception\Http\Controllers\TicketController;

Route::middleware(['auth', 'verified', 'account.active'])->prefix('reception')->name('reception.')->group(function () {
    Route::get('/', [ReceptionController::class, 'index'])->name('dashboard')->middleware('can:reception.dashboard.view');

    Route::get('attendance', [AttendanceController::class, 'index'])->name('attendance.index')->middleware('can:reception.attendance.view');
    Route::get('attendance/export', [AttendanceController::class, 'export'])->name('attendance.export')->middleware('can:reception.attendance.export');

    Route::get('directory', [DirectoryController::class, 'index'])->name('directory')->middleware('can:reception.employees.view_directory');

    Route::get('tickets', [TicketController::class, 'index'])->name('tickets.index')->middleware('can:reception.tickets.view');
    Route::get('tickets/create', [TicketController::class, 'create'])->name('tickets.create')->middleware('can:reception.tickets.create');
    Route::post('tickets', [TicketController::class, 'store'])->name('tickets.store')->middleware('can:reception.tickets.create');
    Route::get('tickets/{ticket}', [TicketController::class, 'show'])->name('tickets.show')->middleware('can:reception.tickets.view');
    Route::post('tickets/{ticket}/comments', [TicketController::class, 'comment'])->name('tickets.comment')->middleware('can:reception.tickets.comment');
    Route::post('tickets/{ticket}/attachments', [TicketController::class, 'attach'])->name('tickets.attach')->middleware('can:reception.tickets.attachments.upload');

    Route::get('technicians', [ReceptionController::class, 'technicians'])->name('technicians')->middleware('can:reception.it_availability.view');
});
