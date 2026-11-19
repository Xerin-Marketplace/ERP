<?php

namespace Modules\Finance\Services;

use Illuminate\Support\Facades\DB;
use Modules\Finance\Models\BankAccount;
use Modules\Finance\Models\CustomerInvoice;
use Modules\Finance\Models\ExpenseClaim;
use Modules\Finance\Models\GeneralLedgerEntry;
use Modules\Finance\Models\Journal;
use Modules\Finance\Models\SupplierInvoice;

class FinanceDashboardService
{
    public function __construct(
        private LedgerService $ledger,
        private PayableService $payables,
        private ReceivableService $receivables,
        private BankService $banks,
    ) {}

    /** All KPIs derived from posted GL + real documents. Company-scoped. */
    public function metrics(int $companyId, ?string $from = null, ?string $to = null): array
    {
        $revenue = $this->ledger->incomeStatement($companyId, $from, $to)['revenue']->sum('balance');
        $expenses = $this->ledger->incomeStatement($companyId, $from, $to)['expenses']->sum('balance');

        $bankBalance = BankAccount::where('company_id', $companyId)->where('is_active', true)->get()
            ->sum(fn ($b) => $this->banks->bookBalance($b, $to));

        $arOutstanding = (float) CustomerInvoice::where('company_id', $companyId)
            ->whereIn('status', ['posted', 'partially_paid'])
            ->sum(DB::raw('total - received_amount'));
        $apOutstanding = (float) SupplierInvoice::where('company_id', $companyId)
            ->whereIn('status', ['posted', 'partially_paid'])
            ->sum(DB::raw('total - paid_amount'));

        return [
            'total_revenue'   => $revenue,
            'total_expenses'  => $expenses,
            'net_profit'      => $revenue - $expenses,
            'bank_balance'    => $bankBalance,
            'ar_outstanding'  => $arOutstanding,
            'ap_outstanding'  => $apOutstanding,
            'ar_aging'        => $this->receivables->aging($companyId),
            'ap_aging'        => $this->payables->aging($companyId),
            'overdue_receivables' => CustomerInvoice::where('company_id', $companyId)
                ->whereIn('status', ['posted', 'partially_paid'])->whereDate('due_date', '<', now())->count(),
            'overdue_payables' => SupplierInvoice::where('company_id', $companyId)
                ->whereIn('status', ['posted', 'partially_paid'])->whereDate('due_date', '<', now())->count(),
            'journals' => [
                'draft' => Journal::where('company_id', $companyId)->where('status', 'draft')->count(),
                'submitted' => Journal::where('company_id', $companyId)->where('status', 'submitted')->count(),
                'posted' => Journal::where('company_id', $companyId)->where('status', 'posted')->count(),
            ],
            'pending_expenses' => ExpenseClaim::where('company_id', $companyId)
                ->whereIn('status', ['submitted', 'manager_review', 'finance_review'])->count(),
        ];
    }
}
