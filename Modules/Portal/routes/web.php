<?php
use Illuminate\Support\Facades\Route;
use Modules\Portal\Http\Controllers\{PortalController, ServiceDeskController};

// Customer-facing portal
Route::middleware(['web','auth'])->prefix('portal')->name('portal.')->group(function () {
    Route::get('/', [PortalController::class,'dashboard'])->name('dashboard');
    Route::get('tracking', [PortalController::class,'tracking'])->name('tracking');
    Route::get('tickets', [PortalController::class,'tickets'])->name('tickets');
    Route::post('tickets', [PortalController::class,'storeTicket'])->name('tickets.store');
    Route::get('tickets/{ticket}', [PortalController::class,'ticketShow'])->name('tickets.show');
    Route::post('tickets/{ticket}/reply', [PortalController::class,'replyTicket'])->name('tickets.reply');
    Route::get('complaints', [PortalController::class,'complaints'])->name('complaints');
    Route::post('complaints', [PortalController::class,'storeComplaint'])->name('complaints.store');
    Route::get('claims', [PortalController::class,'claims'])->name('claims');
    Route::post('claims', [PortalController::class,'storeClaim'])->name('claims.store');
    Route::get('statement', [PortalController::class,'statement'])->name('statement');
    Route::post('feedback', [PortalController::class,'feedback'])->name('feedback');
    Route::post('privacy', [PortalController::class,'privacy'])->name('privacy');
    Route::get('messages', [PortalController::class,'messages'])->name('messages');
});

// Internal customer-service desk
Route::middleware(['web','auth'])->prefix('service-desk')->name('desk.')->group(function () {
    Route::get('dashboard', [ServiceDeskController::class,'dashboard'])->name('dashboard');
    Route::get('customers/{customer}', [ServiceDeskController::class,'customer360'])->name('customer360');
    Route::get('tickets', [ServiceDeskController::class,'tickets'])->name('tickets');
    Route::post('tickets/{ticket}/reply', [ServiceDeskController::class,'ticketReply'])->name('tickets.reply');
    Route::get('complaints', [ServiceDeskController::class,'complaints'])->name('complaints');
    Route::post('complaints/{complaint}/transition', [ServiceDeskController::class,'complaintTransition'])->name('complaints.transition');
    Route::get('claims', [ServiceDeskController::class,'claims'])->name('claims');
    Route::get('claims/{claim}', [ServiceDeskController::class,'claimShow'])->name('claims.show');
    Route::post('claims/{claim}/investigate', [ServiceDeskController::class,'investigate'])->name('claims.investigate');
    Route::post('claims/{claim}/decide', [ServiceDeskController::class,'decide'])->name('claims.decide');
    Route::post('claims/{claim}/settle', [ServiceDeskController::class,'settle'])->name('claims.settle');
    Route::get('privacy', [ServiceDeskController::class,'privacy'])->name('privacy');
});
