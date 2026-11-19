<?php

use Illuminate\Support\Facades\Route;
use Modules\Finance\Http\Controllers\FinanceController as Fin;

Route::middleware(['auth', 'verified', 'account.active'])->prefix('finance')->name('finance.')->group(function () {

    Route::get('/', fn () => redirect()->route('finance.dashboard'))->name('home');
    Route::get('dashboard', [Fin::class, 'dashboard'])->name('dashboard')->middleware('can:finance.dashboard.view');
    Route::get('reports', [Fin::class, 'reports'])->name('reports')->middleware('can:finance.reports.view');
    Route::get('reports/trial-balance', [Fin::class, 'trialBalance'])->name('reports.trial-balance')->middleware('can:finance.reports.view');
    Route::get('settings', [Fin::class, 'settings'])->name('settings')->middleware('can:finance.settings.manage');

    Route::get('accounts', [Fin::class, 'accounts'])->name('accounts.index')->middleware('can:finance.accounts.view');

    Route::get('journals', [Fin::class, 'journals'])->name('journals.index')->middleware('can:finance.journals.view');
    Route::post('journals', [Fin::class, 'storeJournal'])->name('journals.store')->middleware('can:finance.journals.create');
    Route::post('journals/{journal}/submit', [Fin::class, 'submitJournal'])->name('journals.submit')->middleware('can:finance.journals.submit');
    Route::post('journals/{journal}/approve', [Fin::class, 'approveJournal'])->name('journals.approve')->middleware('can:finance.journals.approve');
    Route::post('journals/{journal}/post', [Fin::class, 'postJournal'])->name('journals.post')->middleware('can:finance.journals.post');
    Route::post('journals/{journal}/reverse', [Fin::class, 'reverseJournal'])->name('journals.reverse')->middleware('can:finance.journals.reverse');

    Route::get('ledger', [Fin::class, 'ledger'])->name('ledger.index')->middleware('can:finance.ledger.view');

    Route::get('payables', [Fin::class, 'payables'])->name('payables.index')->middleware('can:finance.payables.manage');
    Route::post('payables/{invoice}/post', [Fin::class, 'postPayable'])->name('payables.post')->middleware('can:finance.payables.manage');

    Route::get('receivables', [Fin::class, 'receivables'])->name('receivables.index')->middleware('can:finance.receivables.manage');
    Route::post('receivables/{invoice}/post', [Fin::class, 'postReceivable'])->name('receivables.post')->middleware('can:finance.receivables.manage');

    Route::get('expenses', [Fin::class, 'expenses'])->name('expenses.index')->middleware('can:finance.expenses.manage');
    Route::post('expenses/{claim}/post', [Fin::class, 'postExpense'])->name('expenses.post')->middleware('can:finance.expenses.manage');

    Route::get('payments', [Fin::class, 'payments'])->name('payments.index')->middleware('can:finance.payments.manage');
    Route::get('banks', [Fin::class, 'banks'])->name('banks.index')->middleware('can:finance.banks.manage');
    Route::get('budgets', [Fin::class, 'budgets'])->name('budgets.index')->middleware('can:finance.budgets.manage');
    Route::get('assets', [Fin::class, 'assets'])->name('assets.index')->middleware('can:finance.assets.manage');
});
