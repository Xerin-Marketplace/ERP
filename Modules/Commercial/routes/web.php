<?php

use Illuminate\Support\Facades\Route;
use Modules\Commercial\Http\Controllers\CommercialController as C;

// Public signed quotation link (no auth — token-gated, rate-limited)
Route::middleware('throttle:30,1')->group(function () {
    Route::get('/q/{token}', [C::class, 'publicQuote'])->name('commercial.quote.public');
    Route::post('/q/{token}', [C::class, 'publicQuoteRespond'])->name('commercial.quote.respond');
});

Route::middleware(['auth', 'verified', 'account.active'])->prefix('commercial')->name('commercial.')->group(function () {
    Route::get('/dashboard', [C::class, 'dashboard'])->name('dashboard')->middleware('can:commercial.view');

    Route::get('/customers', [C::class, 'customers'])->name('customers.index')->middleware('can:customer.view');
    Route::get('/customers/create', [C::class, 'customerCreate'])->name('customers.create')->middleware('can:customer.create');
    Route::post('/customers', [C::class, 'customerStore'])->name('customers.store')->middleware('can:customer.create');
    Route::get('/customers/{customer}', [C::class, 'customerShow'])->name('customers.show')->middleware('can:customer.view');
    Route::get('/customers/{customer}/edit', [C::class, 'customerEdit'])->name('customers.edit')->middleware('can:customer.update');
    Route::put('/customers/{customer}', [C::class, 'customerUpdate'])->name('customers.update')->middleware('can:customer.update');

    Route::get('/leads', [C::class, 'leads'])->name('leads.index')->middleware('can:lead.view');
    Route::post('/leads', [C::class, 'leadStore'])->name('leads.store')->middleware('can:lead.create');
    Route::post('/leads/{lead}/transition', [C::class, 'leadTransition'])->name('leads.transition')->middleware('can:lead.update');
    Route::post('/leads/{lead}/convert', [C::class, 'leadConvert'])->name('leads.convert')->middleware('can:lead.update');

    Route::get('/inquiries', [C::class, 'inquiries'])->name('inquiries.index')->middleware('can:inquiry.view');
    Route::get('/inquiries/create', [C::class, 'inquiryCreate'])->name('inquiries.create')->middleware('can:inquiry.create');
    Route::post('/inquiries', [C::class, 'inquiryStore'])->name('inquiries.store')->middleware('can:inquiry.create');
    Route::get('/inquiries/{inquiry}', [C::class, 'inquiryShow'])->name('inquiries.show')->middleware('can:inquiry.view');
    Route::post('/inquiries/{inquiry}/transition', [C::class, 'inquiryTransition'])->name('inquiries.transition')->middleware('can:inquiry.update');
    Route::post('/inquiries/{inquiry}/assess', [C::class, 'inquiryAssess'])->name('inquiries.assess')->middleware('can:inquiry.assess');

    Route::get('/quotations', [C::class, 'quotations'])->name('quotations.index')->middleware('can:quotation.view');
    Route::get('/quotations/create', [C::class, 'quotationCreate'])->name('quotations.create')->middleware('can:quotation.create');
    Route::post('/quotations', [C::class, 'quotationStore'])->name('quotations.store')->middleware('can:quotation.create');
    Route::get('/quotations/{quotation}', [C::class, 'quotationShow'])->name('quotations.show')->middleware('can:quotation.view');
    Route::get('/quotations/{quotation}/print', [C::class, 'quotationPrint'])->name('quotations.print')->middleware('can:quotation.view');
    Route::post('/quotations/{quotation}/transition', [C::class, 'quotationTransition'])->name('quotations.transition')->middleware('can:quotation.update');

    Route::get('/rate-cards', [C::class, 'rateCards'])->name('rate-cards.index')->middleware('can:pricing.view');
    Route::post('/rate-cards', [C::class, 'rateCardStore'])->name('rate-cards.store')->middleware('can:pricing.manage');
    Route::post('/rate-cards/{rateCard}/approve', [C::class, 'rateCardApprove'])->name('rate-cards.approve')->middleware('can:pricing.approve');
});
