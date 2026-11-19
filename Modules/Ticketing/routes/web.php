<?php

use Illuminate\Support\Facades\Route;
use Modules\Ticketing\Http\Controllers\AdminController;
use Modules\Ticketing\Http\Controllers\ApprovalController;
use Modules\Ticketing\Http\Controllers\CatalogController;
use Modules\Ticketing\Http\Controllers\TaskController;
use Modules\Ticketing\Http\Controllers\TicketController;

Route::middleware(['auth', 'verified', 'account.active'])->prefix('ticketing')->name('ticketing.')->group(function () {

    Route::get('dashboard', [TicketController::class, 'dashboard'])->name('dashboard');
    Route::get('my', [TicketController::class, 'my'])->name('my');
    Route::get('catalog', [CatalogController::class, 'index'])->name('catalog');
    Route::get('catalog/{service}/request', [CatalogController::class, 'request'])->name('catalog.request');
    Route::post('catalog/{service}/request', [CatalogController::class, 'submit'])->name('catalog.submit');
    Route::get('catalog/manage', [CatalogController::class, 'manage'])->name('catalog.manage');
    Route::post('catalog/manage', [CatalogController::class, 'store'])->name('catalog.store');

    Route::get('approvals', [ApprovalController::class, 'index'])->name('approvals.index');
    Route::post('approvals/{approval}/decide', [ApprovalController::class, 'decide'])->name('approvals.decide');

    Route::get('tasks', [TaskController::class, 'index'])->name('tasks.index');
    Route::post('tasks', [TaskController::class, 'store'])->name('tasks.store');
    Route::post('tasks/{task}/status', [TaskController::class, 'updateStatus'])->name('tasks.status');

    Route::get('attachments/{attachment}', [TicketController::class, 'downloadAttachment'])->name('attachments.download');

    /* Admin */
    Route::get('admin/teams', [AdminController::class, 'teams'])->name('admin.teams');
    Route::post('admin/teams', [AdminController::class, 'storeTeam'])->name('admin.teams.store');
    Route::post('admin/teams/{team}/members', [AdminController::class, 'syncMembers'])->name('admin.teams.members');
    Route::get('admin/sla', [AdminController::class, 'sla'])->name('admin.sla');
    Route::post('admin/sla', [AdminController::class, 'storeSla'])->name('admin.sla.store');
    Route::get('admin/escalations', [AdminController::class, 'escalations'])->name('admin.escalations');
    Route::post('admin/escalations', [AdminController::class, 'storeEscalation'])->name('admin.escalations.store');
    Route::get('admin/automation', [AdminController::class, 'automation'])->name('admin.automation');
    Route::post('admin/automation', [AdminController::class, 'storeAutomation'])->name('admin.automation.store');
    Route::post('admin/automation/{rule}/toggle', [AdminController::class, 'toggleAutomation'])->name('admin.automation.toggle');
    Route::get('admin/reports', [AdminController::class, 'reports'])->name('admin.reports');

    Route::get('kb', [AdminController::class, 'kbIndex'])->name('kb.index');
    Route::post('kb', [AdminController::class, 'kbStore'])->name('kb.store');
    Route::get('kb/{article}', [AdminController::class, 'kbShow'])->name('kb.show');

    Route::get('/', [TicketController::class, 'index'])->name('index');
    Route::get('create', [TicketController::class, 'create'])->name('create');
    Route::post('/', [TicketController::class, 'store'])->name('store');
    Route::get('{ticket}', [TicketController::class, 'show'])->name('show');
    Route::post('{ticket}/comment', [TicketController::class, 'comment'])->name('comment');
    Route::post('{ticket}/assign', [TicketController::class, 'assign'])->name('assign');
    Route::post('{ticket}/transition/{to}', [TicketController::class, 'transition'])->name('transition')->where('to', '[a-z_]+');
});
