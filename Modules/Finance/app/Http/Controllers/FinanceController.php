<?php

namespace Modules\Finance\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\ValidationException;
use Modules\Finance\Models\BankAccount;
use Modules\Finance\Models\Budget;
use Modules\Finance\Models\ChartOfAccount;
use Modules\Finance\Models\CustomerInvoice;
use Modules\Finance\Models\ExpenseClaim;
use Modules\Finance\Models\FinancialPeriod;
use Modules\Finance\Models\FixedAsset;
use Modules\Finance\Models\Journal;
use Modules\Finance\Models\SupplierInvoice;
use Modules\Finance\Services\AccountService;
use Modules\Finance\Services\BankService;
use Modules\Finance\Services\BudgetService;
use Modules\Finance\Services\ExpenseService;
use Modules\Finance\Services\FinanceDashboardService;
use Modules\Finance\Services\FinancePeriodService;
use Modules\Finance\Services\FixedAssetService;
use Modules\Finance\Services\JournalService;
use Modules\Finance\Services\LedgerService;
use Modules\Finance\Services\PayableService;
use Modules\Finance\Services\PaymentService;
use Modules\Finance\Services\ReceivableService;
use Modules\Organization\Models\Company;

class FinanceController extends Controller
{
    public function __construct(
        private FinanceDashboardService $dashboard,
        private JournalService $journals,
        private AccountService $accounts,
        private LedgerService $ledger,
        private FinancePeriodService $periods,
        private PayableService $payables,
        private ReceivableService $receivables,
        private ExpenseService $expenses,
        private PaymentService $payments,
        private BankService $banks,
        private BudgetService $budgets,
        private FixedAssetService $assets,
    ) {}

    private function companyId(Request $r): int
    {
        return (int) ($r->user()->employee?->company_id ?? Company::firstOrFail()->id);
    }

    public function dashboard(Request $r)
    {
        return view('finance::dashboard', ['metrics' => $this->dashboard->metrics($this->companyId($r))]);
    }

    public function accounts(Request $r)
    {
        $accounts = ChartOfAccount::where('company_id', $this->companyId($r))
            ->orderBy('code')->with('parent')->get();
        return view('finance::accounts.index', compact('accounts'));
    }

    public function journals(Request $r)
    {
        $journals = Journal::where('company_id', $this->companyId($r))
            ->with('lines', 'period')->latest()->paginate(30);
        $accounts = ChartOfAccount::where('company_id', $this->companyId($r))
            ->where('postable', true)->where('is_active', true)->orderBy('code')->get();
        $openPeriods = FinancialPeriod::where('company_id', $this->companyId($r))
            ->where('status', 'open')->orderBy('starts_on')->get();
        return view('finance::journals.index', compact('journals', 'accounts', 'openPeriods'));
    }

    public function storeJournal(Request $r)
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
        $journal = $this->journals->create($data, $r->user()->id);
        return back()->with('status', "Journal {$journal->reference} created.");
    }

    public function submitJournal(Request $r, Journal $journal)
    {
        $this->journals->submit($journal, $r->user()->id);
        return back()->with('status', "Journal {$journal->reference} submitted.");
    }

    public function approveJournal(Request $r, Journal $journal)
    {
        $this->journals->approve($journal, $r->user()->id);
        return back()->with('status', "Journal {$journal->reference} approved.");
    }

    public function postJournal(Request $r, Journal $journal)
    {
        $this->journals->post($journal, $r->user()->id);
        return back()->with('status', "Journal {$journal->reference} posted.");
    }

    public function reverseJournal(Request $r, Journal $journal)
    {
        $reversal = $this->journals->reverse($journal, $r->user()->id);
        return back()->with('status', "Reversed via {$reversal->reference}.");
    }

    public function ledger(Request $r)
    {
        $accounts = ChartOfAccount::where('company_id', $this->companyId($r))
            ->where('postable', true)->orderBy('code')->get();
        $data = null;
        if ($r->filled('account_id')) {
            $data = $this->ledger->accountLedger((int) $r->account_id, $r->from, $r->to);
        }
        return view('finance::ledger.index', compact('accounts', 'data'));
    }

    public function trialBalance(Request $r)
    {
        $tb = $this->ledger->trialBalance($this->companyId($r));
        return view('finance::reports.trial-balance', compact('tb'));
    }

    public function payables(Request $r)
    {
        $invoices = SupplierInvoice::where('company_id', $this->companyId($r))
            ->with('supplier', 'lines')->latest()->paginate(30);
        $aging = $this->payables->aging($this->companyId($r));
        return view('finance::payables.index', compact('invoices', 'aging'));
    }

    public function postPayable(Request $r, SupplierInvoice $invoice)
    {
        $this->payables->post($invoice, $r->user()->id);
        return back()->with('status', "Invoice {$invoice->reference} posted to GL.");
    }

    public function receivables(Request $r)
    {
        $invoices = CustomerInvoice::where('company_id', $this->companyId($r))
            ->with('customer', 'lines')->latest()->paginate(30);
        $aging = $this->receivables->aging($this->companyId($r));
        return view('finance::receivables.index', compact('invoices', 'aging'));
    }

    public function postReceivable(Request $r, CustomerInvoice $invoice)
    {
        $this->receivables->post($invoice, $r->user()->id);
        return back()->with('status', "Invoice {$invoice->reference} posted to GL.");
    }

    public function expenses(Request $r)
    {
        $claims = ExpenseClaim::where('company_id', $this->companyId($r))
            ->with('category', 'employee')->latest()->paginate(30);
        return view('finance::expenses.index', compact('claims'));
    }

    public function postExpense(Request $r, ExpenseClaim $claim)
    {
        $this->expenses->post($claim, $r->user()->id);
        return back()->with('status', "Expense {$claim->reference} posted.");
    }

    public function payments(Request $r)
    {
        $payments = \Modules\Finance\Models\PaymentRecord::where('company_id', $this->companyId($r))
            ->with('allocations')->latest()->paginate(30);
        return view('finance::payments.index', compact('payments'));
    }

    public function banks(Request $r)
    {
        $accounts = BankAccount::where('company_id', $this->companyId($r))->get()
            ->map(fn ($b) => ['account' => $b, 'book' => $this->banks->bookBalance($b),
                            'statement' => $this->banks->statementBalance($b)]);
        return view('finance::banks.index', ['accounts' => $accounts]);
    }

    public function budgets(Request $r)
    {
        $budgets = Budget::where('company_id', $this->companyId($r))->with('lines', 'year')->latest()->get();
        return view('finance::budgets.index', compact('budgets'));
    }

    public function assets(Request $r)
    {
        $assets = FixedAsset::where('company_id', $this->companyId($r))->with('category')->latest()->get();
        return view('finance::assets.index', compact('assets'));
    }

    public function reports(Request $r)
    {
        $companyId = $this->companyId($r);
        return view('finance::reports.index', [
            'tb' => $this->ledger->trialBalance($companyId),
            'is' => $this->ledger->incomeStatement($companyId),
            'bs' => $this->ledger->balanceSheet($companyId),
        ]);
    }

    public function settings(Request $r)
    {
        $companyId = $this->companyId($r);
        return view('finance::settings.index', [
            'setting' => \Modules\Finance\Models\FinancialSetting::where('company_id', $companyId)->first(),
            'years' => \Modules\Finance\Models\FinancialYear::where('company_id', $companyId)->with('periods')->get(),
            'mappings' => \DB::table('account_mappings')->where('company_id', $companyId)->get(),
            'accounts' => ChartOfAccount::where('company_id', $companyId)->orderBy('code')->get(),
        ]);
    }
}
