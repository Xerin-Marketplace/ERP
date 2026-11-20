<?php

use Illuminate\Support\Facades\Route;
use Modules\Commercial\Http\Controllers\Api\CommercialApiController as A;

Route::middleware('auth:sanctum')->prefix('v1')->group(function () {
    Route::get('customers', [A::class, 'customers'])->middleware('can:customer.view');
    Route::post('customers', [A::class, 'customerStore'])->middleware('can:customer.create');
    Route::get('customers/{customer}', [A::class, 'customerShow'])->middleware('can:customer.view');

    Route::get('leads', [A::class, 'leads'])->middleware('can:lead.view');
    Route::post('leads', [A::class, 'leadStore'])->middleware('can:lead.create');
    Route::post('leads/{lead}/convert', [A::class, 'leadConvert'])->middleware('can:lead.update');

    Route::get('inquiries', [A::class, 'inquiries'])->middleware('can:inquiry.view');
    Route::post('inquiries', [A::class, 'inquiryStore'])->middleware('can:inquiry.create');

    Route::get('quotations', [A::class, 'quotations'])->middleware('can:quotation.view');
    Route::post('quotations', [A::class, 'quotationStore'])->middleware('can:quotation.create');
    Route::get('quotations/{quotation}', [A::class, 'quotationShow'])->middleware('can:quotation.view');
    Route::post('quotations/{quotation}/{action}', [A::class, 'quotationAction'])
        ->whereIn('action', ['submit','approve','send'])->middleware('can:quotation.update');

    Route::get('rate-cards', [A::class, 'rateCards'])->middleware('can:pricing.view');
    Route::post('pricing/quote', [A::class, 'priceQuote'])->middleware('can:pricing.view');

    Route::get('commercial-handover', [A::class, 'handovers'])->middleware('can:commercial.handover.view');
});
