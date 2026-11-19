<?php

namespace Modules\Finance\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Finance\Models\ChartOfAccount;
use Modules\Finance\Models\CustomerInvoice;
use Modules\Finance\Models\Journal;
use Modules\Finance\Models\SupplierInvoice;
use Modules\Finance\Services\FinanceDashboardService;
use Modules\Finance\Services\JournalService;
use Modules\Finance\Services\LedgerService;
use Modules\Finance\Services\PayableService;
use Modules\Finance\Services\PaymentService;
use Modules\Finance\Services\ReceivableService;
use Modules\Organization\Models\Company;

class FinanceApiController extends Controller
{
    public function __construct(
        private FinanceDashboardService $dashboard,
        private JournalService $journals,
        private LedgerService $ledger,
        private PayableService $payables,
        private ReceivableService $receivables,
        private PaymentService $payments,
    ) {}

    private function companyId(Request $r): int
    {
        return (int) ($r->user()->employee?->company_id ?? Company::firstOrFail()->id);
    }

    private function ok(mixed $data, int $status = 200)
    {
        return response()->json(['success' => true, 'data' => $data], $status);
    }

    public function dashboard(Request $r)
    {
        return $this->ok($this->dashboard->metrics($this->companyId($r), $r->from, $r->to));
    }

    public function accounts(Request $r)
    {
        return $this->ok(ChartOfAccount::where('company_id', $this->companyId($r))->orderBy('code')->get());
    }

    public function journals(Request $r)
    {
        return $this->ok(Journal::where('company_id', $this->companyId($r))
            ->with('lines')->latest()->paginate(25));
    }

    public function createJournal(Request $r)
    {
        $data = $r->validate([
            'period_id' => 'required|integer', 'journal_date' => 'required|date',
            'type' => 'nullable|string|max:30', 'memo' => 'nullable|string|max:500',
            'lines' => 'required|array|min:2',
            'lines.*.account_id' => 'required|integer',
            'lines.*.debit' => 'nullable|numeric|min:0',
            'lines.*.credit' => 'nullable|numeric|min:0',
        ]);
        $data['company_id'] = $this->companyId($r);
        return $this->ok($this->journals->create($data, $r->user()->id)->load('lines'), 201);
    }

    public function submitJournal(Request $r, Journal $journal)
    {
        return $this->ok($this->journals->submit($journal, $r->user()->id));
    }

    public function approveJournal(Request $r, Journal $journal)
    {
        return $this->ok($this->journals->approve($journal, $r->user()->id));
    }

    public function postJournal(Request $r, Journal $journal)
    {
        return $this->ok($this->journals->post($journal, $r->user()->id));
    }

    public function reverseJournal(Request $r, Journal $journal)
    {
        return $this->ok($this->journals->reverse($journal, $r->user()->id, $r->memo));
    }

    public function ledger(Request $r)
    {
        $r->validate(['account_id' => 'required|integer']);
        return $this->ok($this->ledger->accountLedger((int) $r->account_id, $r->from, $r->to));
    }

    public function trialBalance(Request $r)
    {
        return $this->ok($this->ledger->trialBalance($this->companyId($r)));
    }

    public function payables(Request $r)
    {
        return $this->ok(SupplierInvoice::where('company_id', $this->companyId($r))
            ->with('supplier', 'lines')->latest()->paginate(25));
    }

    public function postPayable(Request $r, SupplierInvoice $invoice)
    {
        return $this->ok($this->payables->post($invoice, $r->user()->id));
    }

    public function receivables(Request $r)
    {
        return $this->ok(CustomerInvoice::where('company_id', $this->companyId($r))
            ->with('customer', 'lines')->latest()->paginate(25));
    }

    public function postReceivable(Request $r, CustomerInvoice $invoice)
    {
        return $this->ok($this->receivables->post($invoice, $r->user()->id));
    }

    public function recordPayment(Request $r)
    {
        $data = $r->validate([
            'direction' => 'required|in:in,out', 'method' => 'required|string|max:30',
            'amount' => 'required|numeric|min:0.01', 'payment_date' => 'required|date',
            'bank_account_id' => 'nullable|integer', 'payer_payee' => 'nullable|string',
            'external_ref' => 'nullable|string', 'document_type' => 'required|string',
            'document_id' => 'required|integer',
        ]);
        $data['company_id'] = $this->companyId($r);
        return $this->ok($this->payments->record($data, $r->user()->id), 201);
    }
}
