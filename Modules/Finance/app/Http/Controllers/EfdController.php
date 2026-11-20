<?php

namespace Modules\Finance\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Finance\Models\CustomerInvoice;
use Modules\Finance\Models\EfdReceipt;
use Modules\Finance\Services\EfdReceiptService;

class EfdController extends Controller
{
    public function __construct(private EfdReceiptService $svc) {}

    public function index(Request $r)
    {
        return view('finance::efd.index', [
            'receipts' => EfdReceipt::with('invoice:id,reference,total,currency,customer_id', 'invoice.customer:id,name')
                ->when($r->status, fn ($q, $v) => $q->where('status', $v))
                ->latest()->paginate(30),
            'enabled' => $this->svc->isEnabled(),
        ]);
    }

    public function issue(CustomerInvoice $invoice)
    {
        $receipt = $this->svc->issue($invoice, auth()->id());
        return back()->with('success', "Receipt {$receipt->reference} drafted.");
    }

    public function queue(EfdReceipt $receipt)
    {
        $this->svc->queue($receipt);
        return back()->with('success', "Receipt {$receipt->reference} queued.");
    }

    public function void(Request $r, EfdReceipt $receipt)
    {
        $d = $r->validate(['reason' => 'required|string|max:255']);
        $this->svc->void($receipt, $d['reason']);
        return back()->with('success', "Receipt {$receipt->reference} voided.");
    }
}
