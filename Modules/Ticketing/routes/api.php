<?php

use Illuminate\Support\Facades\Route;
use Modules\Ticketing\Http\Controllers\Api\V1\TicketingApiController as Api;

Route::prefix('v1')->name('ticketing.')->middleware(['auth:sanctum', 'account.active'])->group(function () {

    Route::get('service-catalog', [Api::class, 'catalog'])->name('catalog');
    Route::get('ticketing/dashboard', [Api::class, 'dashboard'])->name('dashboard');
    Route::get('ticketing/reports', [Api::class, 'reports'])->name('reports');

    Route::get('tickets', [Api::class, 'index'])->name('index');
    Route::post('tickets', [Api::class, 'store'])->name('store');
    Route::get('tickets/{ticket}', [Api::class, 'show'])->name('show');
    Route::put('tickets/{ticket}', [Api::class, 'update'])->name('update');
    Route::post('tickets/{ticket}/assign', [Api::class, 'assign'])->name('assign');
    Route::post('tickets/{ticket}/comments', [Api::class, 'comment'])->name('comments');
    Route::post('tickets/{ticket}/approve', [Api::class, 'approve'])->name('approve');
    Route::post('tickets/{ticket}/reject', [Api::class, 'reject'])->name('reject');
    Route::post('tickets/{ticket}/resolve', fn (\Illuminate\Http\Request $r, \Modules\Ticketing\Models\Ticket $ticket) => app(Api::class)->transition($r, $ticket, 'resolved'))->name('resolve');
    Route::post('tickets/{ticket}/close', fn (\Illuminate\Http\Request $r, \Modules\Ticketing\Models\Ticket $ticket) => app(Api::class)->transition($r, $ticket, 'closed'))->name('close');
    Route::post('tickets/{ticket}/reopen', fn (\Illuminate\Http\Request $r, \Modules\Ticketing\Models\Ticket $ticket) => app(Api::class)->transition($r, $ticket, 'reopened'))->name('reopen');

    Route::get('tasks', [Api::class, 'tasks'])->name('tasks.index');
    Route::post('tasks', [Api::class, 'storeTask'])->name('tasks.store');
    Route::put('tasks/{task}', [Api::class, 'updateTask'])->name('tasks.update');
});
