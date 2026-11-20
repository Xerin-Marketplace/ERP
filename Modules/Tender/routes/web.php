<?php

use Illuminate\Support\Facades\Route;
use Modules\Tender\Http\Controllers\TenderController;

Route::middleware(['web', 'auth', 'verified', 'account.active'])
    ->prefix('tender')->name('tender.')->group(function () {
        Route::get('/', [TenderController::class, 'dashboard'])->name('dashboard')
            ->middleware('permission:tenders.view');
        Route::get('opportunities', [TenderController::class, 'index'])->name('index')
            ->middleware('permission:tenders.view');
        Route::post('opportunities', [TenderController::class, 'store'])->name('store')
            ->middleware('permission:tenders.create');
        Route::get('{tender}', [TenderController::class, 'show'])->name('show')
            ->middleware('permission:tenders.view');
        Route::post('{tender}/assess', [TenderController::class, 'assess'])->name('assess')
            ->middleware('permission:tenders.assess');
        Route::post('{tender}/decide', [TenderController::class, 'decide'])->name('decide')
            ->middleware('permission:tenders.decide');
        Route::post('{tender}/boq-items', [TenderController::class, 'addBoqItem'])->name('boq.add')
            ->middleware('permission:tenders.boq.manage');
        Route::post('{tender}/submit', [TenderController::class, 'submit'])->name('submit')
            ->middleware('permission:tenders.submit');
        Route::post('{tender}/event', [TenderController::class, 'event'])->name('event')
            ->middleware('permission:tenders.evaluate');
        Route::post('{tender}/award', [TenderController::class, 'award'])->name('award')
            ->middleware('permission:tenders.award');

        /* Phase 2 */
        Route::get('{tender}/intelligence', [TenderController::class, 'intelligence'])->name('intelligence')
            ->middleware('permission:tenders.view');
        Route::post('{tender}/clarifications', [TenderController::class, 'clarify'])->name('clarify')
            ->middleware('permission:tenders.evaluate');
        Route::post('clarifications/{clarification}/respond', [TenderController::class, 'respondClarification'])->name('clarify.respond')
            ->middleware('permission:tenders.evaluate');
        Route::post('{tender}/tasks', [TenderController::class, 'addTask'])->name('tasks.add')
            ->middleware('permission:tenders.manage');
        Route::patch('tasks/{task}', [TenderController::class, 'updateTask'])->name('tasks.update')
            ->middleware('permission:tenders.manage');
        Route::post('{tender}/awards', [TenderController::class, 'recordAward'])->name('awards.record')
            ->middleware('permission:tenders.award');
        Route::post('contracts/{contract}/variations', [TenderController::class, 'variation'])->name('variations.add')
            ->middleware('permission:tenders.contract.manage');
        Route::post('variations/{variation}/approve', [TenderController::class, 'approveVariation'])->name('variations.approve')
            ->middleware('permission:tenders.contract.manage');
    });
