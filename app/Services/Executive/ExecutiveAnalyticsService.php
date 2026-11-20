<?php

namespace App\Services\Executive;

use App\Models\AuditLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Arrival\Models\ReleaseApproval;
use Modules\Cargo\Models\CargoBooking;
use Modules\Cargo\Models\CargoPackage;
use Modules\Commercial\Models\Customer;
use Modules\Delivery\Models\DeliveryOrder;
use Modules\Employee\Models\Employee;
use Modules\Finance\Models\ChartOfAccount;
use Modules\Finance\Models\CustomerInvoice;
use Modules\Finance\Models\GeneralLedgerEntry;
use Modules\Finance\Models\PaymentRecord;
use Modules\Finance\Models\SupplierInvoice;
use Modules\Finance\Services\FinanceDashboardService;
use Modules\Organization\Models\Branch;
use Modules\Organization\Models\Department;
use Modules\Tender\Models\Tender;
use Modules\WorkCenter\Models\WorkTask;

/**
 * Executive Command Center — single aggregation source for every
 * analytics page and API endpoint. All figures trace to real records;
 * no placeholders. Branch-scoped via branch_id on GL/ops tables.
 *
 * Currency rule (spec §18): monetary aggregates come from the GL in
 * posting currency. Branch comparison never mixes AED and TZS rows —
 * each branch row is labelled with its own currency.
 */
class ExecutiveAnalyticsService
{
    public const PRESETS = [
        'today' => 'Today', 'yesterday' => 'Yesterday', 'last7' => 'Last 7 days',
        'last30' => 'Last 30 days', 'last90' => 'Last 90 days',
        'month' => 'Current month', 'prev_month' => 'Previous month',
        'quarter' => 'Current quarter', 'year' => 'Current year', 'prev_year' => 'Previous year',
        'custom' => 'Custom range',
    ];

    public function __construct(private FinanceDashboardService $finance) {}

    /** Resolve [from, to, prevFrom, prevTo] from request params. */
    public function period(string $preset, ?string $from, ?string $to): array
    {
        $today = Carbon::today();
        [$f, $t] = match ($preset) {
            'today'      => [$today->copy(), $today->copy()],
            'yesterday'  => [$today->copy()->subDay(), $today->copy()->subDay()],
            'last7'      => [$today->copy()->subDays(6), $today->copy()],
            'last30'     => [$today->copy()->subDays(29), $today->copy()],
            'last90'     => [$today->copy()->subDays(89), $today->copy()],
            'month'      => [$today->copy()->startOfMonth(), $today->copy()],
            'prev_month' => [$today->copy()->subMonthNoOverflow()->startOfMonth(), $today->copy()->subMonthNoOverflow()->endOfMonth()],
            'quarter'    => [$today->copy()->firstOfQuarter(), $today->copy()],
            'year'       => [$today->copy()->startOfYear(), $today->copy()],
            'prev_year'  => [$today->copy()->subYear()->startOfYear(), $today->copy()->subYear()->endOfYear()],
            'custom'     => [Carbon::parse($from ?? $today), Carbon::parse($to ?? $today)],
            default      => [$today->copy()->startOfMonth(), $today->copy()],
        };
        $days = $f->diffInDays($t) + 1;
        $prevTo = $f->copy()->subDay();
        $prevFrom = $prevTo->copy()->subDays($days - 1);
        return [$f->toDateString(), $t->toDateString(), $prevFrom->toDateString(), $prevTo->toDateString()];
    }

    // ── Financial ───────────────────────────────────────
    /** Revenue/expense totals from posted GL, optionally branch-scoped. */
    private function glSum(int $companyId, string $type, string $from, string $to, ?int $branchId): float
    {
        return (float) GeneralLedgerEntry::where('general_ledger_entries.company_id', $companyId)
            ->join('chart_of_accounts', 'chart_of_accounts.id', '=', 'general_ledger_entries.account_id')
            ->where('chart_of_accounts.type', $type)
            ->whereBetween('general_ledger_entries.entry_date', [$from, $to])
            ->when($branchId, fn ($q) => $q->where('general_ledger_entries.branch_id', $branchId))
            ->selectRaw($this->selectRaw($type))
            ->value('agg') ?? 0.0;
    }

    private function selectRaw(string $type): string
    {
        return $type === 'revenue'
            ? 'COALESCE(SUM(general_ledger_entries.credit - general_ledger_entries.debit),0) as agg'
            : 'COALESCE(SUM(general_ledger_entries.debit - general_ledger_entries.credit),0) as agg';
    }

    /** Monthly buckets for trend charts. */
    public function financeTrend(int $companyId, string $from, string $to, ?int $branchId): array
    {
        $rows = GeneralLedgerEntry::where('general_ledger_entries.company_id', $companyId)
            ->join('chart_of_accounts', 'chart_of_accounts.id', '=', 'general_ledger_entries.account_id')
            ->whereBetween('general_ledger_entries.entry_date', [$from, $to])
            ->when($branchId, fn ($q) => $q->where('general_ledger_entries.branch_id', $branchId))
            ->whereIn('chart_of_accounts.type', ['revenue', 'expense'])
            ->selectRaw("SUBSTR(general_ledger_entries.entry_date,1,7) as bucket, chart_of_accounts.type, SUM(general_ledger_entries.credit - general_ledger_entries.debit) as amt")
            ->groupBy('bucket', 'chart_of_accounts.type')
            ->orderBy('bucket')->get();

        $months = [];
        $c = Carbon::parse($from)->startOfMonth();
        $end = Carbon::parse($to)->startOfMonth();
        while ($c <= $end) { $months[] = $c->format('Y-m'); $c->addMonth(); }

        return [
            'labels' => $months,
            'revenue' => array_map(fn ($m) => (float) ($rows->firstWhere(fn ($r) => $r->bucket === $m && $r->type === 'revenue')?->amt ?? 0), $months),
            'expenses' => array_map(fn ($m) => (float) (-1 * ($rows->firstWhere(fn ($r) => $r->bucket === $m && $r->type === 'expense')?->amt ?? 0)), $months),
        ];
    }

    // ── Executive KPI set ───────────────────────────────
    public function executive(int $companyId, string $from, string $to, string $prevFrom, string $prevTo, ?int $branchId): array
    {
        $rev = $this->glSum($companyId, 'revenue', $from, $to, $branchId);
        $exp = $this->glSum($companyId, 'expense', $from, $to, $branchId);
        $prevRev = $this->glSum($companyId, 'revenue', $prevFrom, $prevTo, $branchId);
        $prevExp = $this->glSum($companyId, 'expense', $prevFrom, $prevTo, $branchId);

        $invoices = CustomerInvoice::where('company_id', $companyId)->whereIn('status', ['posted', 'partially_paid', 'paid']);
        $ar = (float) (clone $invoices)->whereIn('status', ['posted', 'partially_paid'])->sum(DB::raw('total - received_amount'));
        $arOverdue = (float) (clone $invoices)->whereIn('status', ['posted', 'partially_paid'])
            ->whereDate('due_date', '<', now())->sum(DB::raw('total - received_amount'));
        $ap = (float) SupplierInvoice::where('company_id', $companyId)
            ->whereIn('status', ['posted', 'partially_paid'])->sum(DB::raw('total - paid_amount'));

        $cash = fn ($f, $t) => (float) PaymentRecord::where('company_id', $companyId)
            ->where('direction', 'in')->where('status', '!=', 'reversed')
            ->whereBetween('payment_date', [$f, $t])->sum('amount');

        $customers = Customer::where('company_id', $companyId)->when($branchId, fn ($q) => $q->where('branch_id', $branchId));
        $employees = Employee::where('company_id', $companyId)->when($branchId, fn ($q) => $q->where('branch_id', $branchId));
        $tasks = WorkTask::where('company_id', $companyId)->when($branchId, fn ($q) => $q->where('branch_id', $branchId));
        $tenders = Tender::where('company_id', $companyId)->when($branchId, fn ($q) => $q->where('branch_id', $branchId));

        return [
            'revenue' => $this->kpi($rev, $prevRev),
            'expenses' => $this->kpi($exp, $prevExp),
            'net_profit' => $this->kpi($rev - $exp, $prevRev - $prevExp),
            'margin' => $this->kpi($rev > 0 ? round(($rev - $exp) / $rev * 100, 1) : 0,
                $prevRev > 0 ? round(($prevRev - $prevExp) / $prevRev * 100, 1) : null),
            'cash_collected' => $this->kpi($cash($from, $to), $cash($prevFrom, $prevTo)),
            'ar_outstanding' => $this->kpi($ar, null),
            'ar_overdue' => $this->kpi($arOverdue, null),
            'ap_outstanding' => $this->kpi($ap, null),
            'customers' => $this->kpi((clone $customers)->count(),
                (clone $customers)->whereDate('created_at', '<=', $prevTo)->count()),
            'new_customers' => $this->kpi(
                (clone $customers)->whereBetween('created_at', [$from, $to . ' 23:59:59'])->count(), null),
            'employees' => $this->kpi((clone $employees)->where('status', 'active')->count(), null),
            'tasks_open' => $this->kpi((clone $tasks)->whereNotIn('status', ['completed', 'cancelled'])->count(), null),
            'tasks_overdue' => $this->kpi((clone $tasks)->whereNotIn('status', ['completed', 'cancelled'])
                ->whereDate('due_at', '<', now())->count(), null),
            'tenders_active' => $this->kpi((clone $tenders)->whereNotIn('stage', ['awarded', 'lost', 'cancelled'])->count(), null),
            'tenders_won' => $this->kpi((clone $tenders)->where('stage', 'awarded')->count(), null),
        ];
    }

    private function kpi(float|int $current, float|int|null $previous): array
    {
        $delta = $previous === null ? null : $current - $previous;
        $pct = ($previous === null || $previous == 0) ? null : round($delta / abs($previous) * 100, 1);
        return ['value' => $current, 'previous' => $previous, 'delta' => $delta, 'pct' => $pct];
    }

    // ── Branch comparison ───────────────────────────────
    public function branches(int $companyId, string $from, string $to): Collection
    {
        $gl = GeneralLedgerEntry::where('general_ledger_entries.company_id', $companyId)
            ->join('chart_of_accounts', 'chart_of_accounts.id', '=', 'general_ledger_entries.account_id')
            ->whereBetween('general_ledger_entries.entry_date', [$from, $to])
            ->whereNotNull('general_ledger_entries.branch_id')
            ->selectRaw("general_ledger_entries.branch_id, chart_of_accounts.type,
                SUM(CASE WHEN chart_of_accounts.type='revenue' THEN general_ledger_entries.credit - general_ledger_entries.debit
                         WHEN chart_of_accounts.type='expense' THEN general_ledger_entries.debit - general_ledger_entries.credit ELSE 0 END) as amt")
            ->groupBy('general_ledger_entries.branch_id', 'chart_of_accounts.type')
            ->get()->groupBy('branch_id');

        return Branch::where('company_id', $companyId)->orderBy('name')->get()
            ->map(function ($b) use ($gl, $companyId) {
                $rows = $gl->get($b->id, collect());
                $rev = (float) ($rows->firstWhere('type', 'revenue')?->amt ?? 0);
                $exp = (float) ($rows->firstWhere('type', 'expense')?->amt ?? 0);
                return [
                    'branch' => $b,
                    'revenue' => $rev, 'expenses' => $exp, 'profit' => $rev - $exp,
                    'employees' => Employee::where('branch_id', $b->id)->where('status', 'active')->count(),
                    'customers' => Customer::where('company_id', $companyId)->where('branch_id', $b->id)->count(),
                    'open_tasks' => WorkTask::where('branch_id', $b->id)->whereNotIn('status', ['completed', 'cancelled'])->count(),
                ];
            });
    }

    // ── Departments ─────────────────────────────────────
    public function departments(int $companyId, ?int $branchId): Collection
    {
        return Department::where('company_id', $companyId)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->with('branch:id,code,name')->orderBy('name')->get()
            ->map(fn ($d) => [
                'department' => $d,
                'staff' => Employee::where('department_id', $d->id)->where('status', 'active')->count(),
                'tasks_open' => WorkTask::where('department_id', $d->id)->whereNotIn('status', ['completed', 'cancelled'])->count(),
                'tasks_overdue' => WorkTask::where('department_id', $d->id)->whereNotIn('status', ['completed', 'cancelled'])->whereDate('due_at', '<', now())->count(),
                'tasks_done' => WorkTask::where('department_id', $d->id)->where('status', 'completed')->count(),
            ]);
    }

    // ── Sales ───────────────────────────────────────────
    public function sales(int $companyId, string $from, string $to, string $prevFrom, string $prevTo, ?int $branchId): array
    {
        $q = fn ($f, $t) => CustomerInvoice::where('company_id', $companyId)
            ->whereIn('status', ['posted', 'partially_paid', 'paid'])
            ->whereBetween('invoice_date', [$f, $t])
            ->when($branchId, fn ($x) => $x->whereHas('customer', fn ($c) => $c->where('branch_id', $branchId)));

        $cur = $q($from, $to);
        $prev = $q($prevFrom, $prevTo);
        $gross = (float) (clone $cur)->sum('total');
        $count = (clone $cur)->count();

        return [
            'gross_sales' => $this->kpi($gross, (float) (clone $prev)->sum('total')),
            'invoices' => $this->kpi($count, (clone $prev)->count()),
            'avg_invoice' => $this->kpi($count ? $gross / $count : 0, null),
            'by_customer' => (clone $cur)->selectRaw('customer_id, SUM(total) as amt, COUNT(*) as n')
                ->groupBy('customer_id')->orderByDesc('amt')->limit(10)->get()
                ->map(fn ($r) => ['customer' => \Modules\Commercial\Models\Customer::find($r->customer_id), 'amt' => (float) $r->amt, 'n' => $r->n]),
            'by_month' => (clone $cur)->selectRaw("SUBSTR(invoice_date,1,7) as m, SUM(total) as amt")
                ->groupBy('m')->orderBy('m')->pluck('amt', 'm'),
        ];
    }

    // ── Employees ───────────────────────────────────────
    public function employees(int $companyId, ?int $branchId, ?int $deptId): Collection
    {
        return Employee::where('company_id', $companyId)->where('status', 'active')
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->when($deptId, fn ($q) => $q->where('department_id', $deptId))
            ->with(['user:id,name,email', 'department:id,name', 'branch:id,code', 'position:id,title'])
            ->orderBy('employee_number')->get()
            ->map(fn ($e) => [
                'employee' => $e,
                'tasks_open' => WorkTask::where('assignee_id', $e->user_id)->whereNotIn('status', ['completed', 'cancelled'])->count(),
                'tasks_done' => WorkTask::where('assignee_id', $e->user_id)->where('status', 'completed')->count(),
                'tasks_overdue' => WorkTask::where('assignee_id', $e->user_id)->whereNotIn('status', ['completed', 'cancelled'])->whereDate('due_at', '<', now())->count(),
            ]);
    }

    // ── Tasks ───────────────────────────────────────────
    public function tasks(int $companyId, ?int $branchId, ?int $deptId): array
    {
        $q = WorkTask::where('company_id', $companyId)
            ->when($branchId, fn ($x) => $x->where('branch_id', $branchId))
            ->when($deptId, fn ($x) => $x->where('department_id', $deptId));
        $byStatus = (clone $q)->selectRaw('status, COUNT(*) as n')->groupBy('status')->pluck('n', 'status');
        return [
            'by_status' => $byStatus,
            'overdue' => (clone $q)->whereNotIn('status', ['completed', 'cancelled'])->whereDate('due_at', '<', now())->count(),
            'completed_this_month' => (clone $q)->where('status', 'completed')->whereMonth('completed_at', now()->month)->whereYear('completed_at', now()->year)->count(),
            'by_department' => (clone $q)->whereNotNull('department_id')->selectRaw('department_id, COUNT(*) as n')->groupBy('department_id')->pluck('n', 'department_id'),
            'recent' => (clone $q)->with(['department:id,name', 'branch:id,code', 'assignee:id,name'])->latest()->limit(20)->get(),
        ];
    }

    // ── Tenders ─────────────────────────────────────────
    public function tenders(int $companyId, ?int $branchId): array
    {
        $q = Tender::where('company_id', $companyId)
            ->when($branchId, fn ($x) => $x->where('branch_id', $branchId));
        return [
            'by_stage' => (clone $q)->selectRaw('stage, COUNT(*) as n')->groupBy('stage')->pluck('n', 'stage'),
            'pipeline_value' => (float) (clone $q)->whereNotIn('stage', ['awarded', 'lost', 'cancelled'])->sum('estimated_value'),
            'awarded_value' => (float) (clone $q)->where('stage', 'awarded')->sum('submitted_value'),
            'upcoming_deadlines' => (clone $q)->whereNotIn('stage', ['awarded', 'lost', 'cancelled'])
                ->whereNotNull('submission_deadline')->whereDate('submission_deadline', '>=', now())
                ->orderBy('submission_deadline')->limit(10)->get(),
            'recent' => (clone $q)->latest()->limit(10)->get(),
        ];
    }

    // ── Operations pulse (cargo → release → delivery) ───
    public function operations(int $companyId, string $from, string $to, ?int $branchId): array
    {
        $pkg = fn () => CargoPackage::when($branchId, fn ($q) => $q->where('branch_id', $branchId));
        $book = fn () => CargoBooking::when($branchId, fn ($q) => $q->where('branch_id', $branchId));
        $del = fn () => DeliveryOrder::when($branchId, fn ($q) => $q->where('branch_id', $branchId));
        $rel = fn () => ReleaseApproval::when($branchId, fn ($q) => $q->where('branch_id', $branchId));

        $delivered = ['delivered', 'closed', 'collected_by_customer'];
        $pkgDone = $pkg()->whereBetween('created_at', [$from, $to . ' 23:59:59'])->count();
        $delDone = $del()->whereIn('status', $delivered)->count();

        return [
            'packages_received' => $this->kpi($pkgDone, null),
            'in_warehouse' => $this->kpi($pkg()->whereIn('status', ['received', 'identified', 'weighed', 'inspection_passed', 'stored', 'ready'])->count(), null),
            'active_bookings' => $this->kpi($book()->whereIn('status', ['pending', 'confirmed', 'in_transit'])->count(), null),
            'pending_releases' => $this->kpi($rel()->where('status', 'pending')->count(), null),
            'deliveries_completed' => $this->kpi($delDone, null),
            'deliveries_active' => $this->kpi($del()->whereNotIn('status', array_merge($delivered, ['cancelled', 'returned_to_warehouse']))->count(), null),
            'funnel' => [
                'labels' => ['Bookings', 'Received', 'In warehouse', 'Dispatched', 'Delivered'],
                'values' => [
                    $book()->whereBetween('created_at', [$from, $to . ' 23:59:59'])->count(),
                    $pkgDone,
                    $pkg()->whereIn('status', ['received', 'identified', 'weighed', 'inspection_passed', 'stored', 'ready'])->count(),
                    $pkg()->where('status', 'dispatched')->count(),
                    $delDone,
                ],
            ],
        ];
    }

    /** Per-day GL + package series for KPI sparklines (real data only). */
    public function dailySeries(int $companyId, string $from, string $to, ?int $branchId): array
    {
        $rows = GeneralLedgerEntry::where('general_ledger_entries.company_id', $companyId)
            ->join('chart_of_accounts', 'chart_of_accounts.id', '=', 'general_ledger_entries.account_id')
            ->whereBetween('general_ledger_entries.entry_date', [$from, $to])
            ->when($branchId, fn ($q) => $q->where('general_ledger_entries.branch_id', $branchId))
            ->whereIn('chart_of_accounts.type', ['revenue', 'expense'])
            ->selectRaw("SUBSTR(general_ledger_entries.entry_date,1,10) as day, chart_of_accounts.type, SUM(general_ledger_entries.credit - general_ledger_entries.debit) as amt")
            ->groupBy('day', 'chart_of_accounts.type')->get();

        $pkgDays = CargoPackage::when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->whereBetween('created_at', [$from, $to . ' 23:59:59'])
            ->selectRaw("SUBSTR(created_at,1,10) as day, COUNT(*) as n")->groupBy('day')->pluck('n', 'day');

        $days = [];
        for ($c = Carbon::parse($from); $c <= Carbon::parse($to); $c->addDay()) { $days[] = $c->toDateString(); }

        $at = fn ($day, $type) => (float) ($rows->firstWhere(fn ($r) => $r->day === $day && $r->type === $type)?->amt ?? 0);
        $rev = array_map(fn ($d) => $at($d, 'revenue'), $days);
        $exp = array_map(fn ($d) => -1 * $at($d, 'expense'), $days);

        return [
            'labels' => $days,
            'revenue' => $rev,
            'expenses' => $exp,
            'profit' => array_map(fn ($r, $e) => $r - $e, $rev, $exp),
            'packages' => array_map(fn ($d) => (int) ($pkgDays[$d] ?? 0), $days),
        ];
    }

    // ── Activity feed (immutable audit log) ─────────────
    public function activity(int $limit = 40, ?string $action = null): Collection
    {
        return AuditLog::with('user:id,name')
            ->when($action, fn ($q) => $q->where('action', 'like', "{$action}%"))
            ->latest()->limit($limit)->get();
    }

    // ── AR/AP ageing passthrough ────────────────────────
    public function financeMetrics(int $companyId, ?string $from = null, ?string $to = null): array
    {
        return $this->finance->metrics($companyId, $from, $to);
    }
}
