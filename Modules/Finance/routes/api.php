<?php

use Illuminate\Support\Facades\Route;
use Modules\Finance\Http\Controllers\Api\V1\FinanceApiController as Api;

Route::prefix('v1/finance')->name('finance.')->middleware(['auth:sanctum', 'account.active'])->group(function () {

    Route::get('dashboard', [Api::class, 'dashboard'])->name('dashboard');
    Route::get('accounts', [Api::class, 'accounts'])->name('accounts.index');
    Route::get('ledger', [Api::class, 'ledger'])->name('ledger');
    Route::get('reports/trial-balance', [Api::class, 'trialBalance'])->name('reports.trial-balance');

    Route::get('journals', [Api::class, 'journals'])->name('journals.index');
    Route::post('journals', [Api::class, 'createJournal'])->name('journals.create');
    Route::post('journals/{journal}/submit', [Api::class, 'submitJournal'])->name('journals.submit');
    Route::post('journals/{journal}/approve', [Api::class, 'approveJournal'])->name('journals.approve');
    Route::post('journals/{journal}/post', [Api::class, 'postJournal'])->name('journals.post');
    Route::post('journals/{journal}/reverse', [Api::class, 'reverseJournal'])->name('journals.reverse');

    Route::get('payables', [Api::class, 'payables'])->name('payables.index');
    Route::post('payables/{invoice}/post', [Api::class, 'postPayable'])->name('payables.post');
    Route::get('receivables', [Api::class, 'receivables'])->name('receivables.index');
    Route::post('receivables/{invoice}/post', [Api::class, 'postReceivable'])->name('receivables.post');
    Route::post('payments', [Api::class, 'recordPayment'])->name('payments.store');
});
