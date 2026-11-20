<?php
use Illuminate\Support\Facades\Route;
use Modules\Sales\Http\Controllers\SalesController;

Route::middleware(['web','auth'])->prefix('sales')->name('sales.')->group(function () {
    Route::get('/', [SalesController::class,'dashboard'])->name('dashboard');
    Route::get('/pipeline', [SalesController::class,'pipeline'])->name('pipeline');
    Route::post('/opportunities', [SalesController::class,'storeOpportunity'])->name('opportunities.store');
    Route::post('/opportunities/{opportunity}/move', [SalesController::class,'moveOpportunity'])->name('opportunities.move');
    Route::post('/opportunities/{opportunity}/close', [SalesController::class,'closeOpportunity'])->name('opportunities.close');
    Route::get('/targets', [SalesController::class,'targets'])->name('targets');
    Route::post('/targets', [SalesController::class,'storeTarget'])->name('targets.store');
    Route::get('/contracts', [SalesController::class,'contracts'])->name('contracts');
    Route::post('/contracts', [SalesController::class,'storeContract'])->name('contracts.store');
    Route::get('/contracts/{contract}', [SalesController::class,'showContract'])->name('contracts.show');
    Route::post('/contracts/{contract}/transition', [SalesController::class,'transitionContract'])->name('contracts.transition');
    Route::get('/partners', [SalesController::class,'partners'])->name('partners');
    Route::post('/partners', [SalesController::class,'storePartner'])->name('partners.store');
    Route::get('/partners/{partner}', [SalesController::class,'showPartner'])->name('partners.show');
    Route::post('/partners/{partner}/transition', [SalesController::class,'transitionPartner'])->name('partners.transition');
    Route::get('/commissions', [SalesController::class,'commissions'])->name('commissions');
    Route::post('/commission-rules', [SalesController::class,'storeCommissionRule'])->name('commission-rules.store');
    Route::post('/commissions/{commission}/transition', [SalesController::class,'transitionCommission'])->name('commissions.transition');
    Route::get('/simulate', [SalesController::class,'simulate'])->name('simulate');
    Route::get('/approvals', [SalesController::class,'approvals'])->name('approvals');
    Route::post('/approvals', [SalesController::class,'requestApproval'])->name('approvals.store');
    Route::post('/approvals/{approval}/decide', [SalesController::class,'decideApproval'])->name('approvals.decide');
    Route::get('/campaigns', [SalesController::class,'campaigns'])->name('campaigns');
    Route::post('/campaigns', [SalesController::class,'storeCampaign'])->name('campaigns.store');
});
