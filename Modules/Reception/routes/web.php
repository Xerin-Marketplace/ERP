<?php

use Illuminate\Support\Facades\Route;
use Modules\Reception\Http\Controllers\AttendanceController;
use Modules\Reception\Http\Controllers\DirectoryController;
use Modules\Reception\Http\Controllers\ReceptionController;
use Modules\Reception\Http\Controllers\AccessRequestController;
use Modules\Reception\Http\Controllers\ApprovalController;
use Modules\Reception\Http\Controllers\ProvisioningController;
use Modules\Reception\Http\Controllers\PublicAccessController;
use Modules\Reception\Http\Controllers\TicketController;
use Modules\Reception\Http\Controllers\WorkflowAdminController;

// Public access-request portal — unauthenticated, throttled.
Route::prefix('access-request')->name('access.')->middleware('throttle:30,1')->group(function () {
    Route::get('/', [PublicAccessController::class, 'landing'])->name('landing');
    Route::get('new/{type}', [PublicAccessController::class, 'form'])->name('form');
    Route::post('/', [PublicAccessController::class, 'store'])->name('store');
    Route::get('submitted/{token}', [PublicAccessController::class, 'submitted'])->name('submitted');
    Route::get('track', [PublicAccessController::class, 'track'])->name('track');
});

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

    // Access Management — internal ops.
    Route::prefix('access')->name('access.')->group(function () {
        Route::get('dashboard', [AccessRequestController::class, 'dashboard'])->name('dashboard')->middleware('can:reception.access.view');
        Route::get('requests', [AccessRequestController::class, 'index'])->name('requests.index')->middleware('can:reception.access.view');
        Route::get('requests/{accessRequest}', [AccessRequestController::class, 'show'])->name('requests.show')->middleware('can:reception.access.view');
        Route::post('requests/{accessRequest}/review', [AccessRequestController::class, 'startReview'])->name('requests.review')->middleware('can:reception.access.review');
        Route::post('requests/{accessRequest}/info', [AccessRequestController::class, 'requestInfo'])->name('requests.info')->middleware('can:reception.access.review');
        Route::post('requests/{accessRequest}/verify/{verification}', [AccessRequestController::class, 'verify'])->name('requests.verify')->middleware('can:reception.access.verify');
        Route::post('requests/{accessRequest}/route', [AccessRequestController::class, 'route'])->name('requests.route')->middleware('can:reception.access.route');
        Route::post('requests/{accessRequest}/reject', [AccessRequestController::class, 'reject'])->name('requests.reject')->middleware('can:reception.access.route');
        Route::post('requests/{accessRequest}/notes', [AccessRequestController::class, 'note'])->name('requests.notes')->middleware('can:reception.access.view');
        Route::post('requests/{accessRequest}/documents', [AccessRequestController::class, 'uploadDocument'])->name('requests.documents')->middleware('can:reception.access.review');
        Route::get('requests/{accessRequest}/documents/{document}/download', [AccessRequestController::class, 'downloadDocument'])->name('requests.documents.download')->middleware('can:reception.access.view');

        Route::get('approvals', [ApprovalController::class, 'inbox'])->name('approvals.inbox');
        Route::post('approvals/{task}/decide', [ApprovalController::class, 'decide'])->name('approvals.decide');
        Route::post('approvals/{task}/delegate', [ApprovalController::class, 'delegate'])->name('approvals.delegate');

        Route::get('provisioning', [ProvisioningController::class, 'queue'])->name('provisioning.queue')->middleware('can:reception.access.provision');
        Route::get('provisioning/{accessRequest}/configure', [ProvisioningController::class, 'configure'])->name('provisioning.configure')->middleware('can:reception.access.provision');
        Route::post('provisioning/{accessRequest}', [ProvisioningController::class, 'provision'])->name('provisioning.provision')->middleware('can:reception.access.provision');

        Route::get('workflows', [WorkflowAdminController::class, 'index'])->name('workflows.index')->middleware('can:reception.access.workflows.manage');
        Route::post('workflows', [WorkflowAdminController::class, 'store'])->name('workflows.store')->middleware('can:reception.access.workflows.manage');
        Route::post('workflows/{workflow}/versions', [WorkflowAdminController::class, 'newVersion'])->name('workflows.new_version')->middleware('can:reception.access.workflows.manage');
        Route::post('workflows/{workflow}/toggle', [WorkflowAdminController::class, 'toggle'])->name('workflows.toggle')->middleware('can:reception.access.workflows.manage');
    });
});
