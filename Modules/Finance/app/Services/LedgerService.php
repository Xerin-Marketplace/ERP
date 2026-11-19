<?php

namespace Modules\Finance\Services;

use Illuminate\Support\Collection;
use Modules\Finance\Models\ChartOfAccount;
use Modules\Finance\Models\GeneralLedgerEntry;

/** All balances derive exclusively from posted general_ledger_entries. */
class LedgerService
{
    /** General ledger rows with running balance for one account. */
    public function accountLedger(int $accountId, ?string $from = null, ?string $to = null): array
    {
        $account = ChartOfAccount::findOrFail($accountId);
        $opening = $from ? $this->rawBalance($accountId, null, $from) : 0.0;
        $entries = GeneralLedgerEntry::where('account_id', $accountId)
            ->when($from, fn ($q) => $q->whereDate('entry_date', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('entry_date', '<=', $to))
            ->orderBy('entry_date')->orderBy('id')->get();

        $running = $opening;
        $rows = $entries->map(function ($e) use (&$running, $account) {
            $delta = $account->normal_balance === 'debit'
                ? $e->debit - $e->credit : $e->credit - $e->debit;
            $running += $delta;
            return ['entry' => $e, 'balance' => $running];
        });

        return ['account' => $account, 'opening' => $opening, 'rows' => $rows, 'closing' => $running];
    }

    /** Trial balance: debit/credit totals per account; must net to zero. */
    public function trialBalance(int $companyId, ?int $periodId = null, ?string $asOf = null): array
    {
        $accounts = ChartOfAccount::where('company_id', $companyId)->where('postable', true)->get();
        $rows = $accounts->map(function ($a) use ($companyId, $periodId, $asOf) {
            [$d, $c] = $this->rawTotals($companyId, $a->id, $periodId, $asOf);
            return ['account' => $a, 'debit' => $d, 'credit' => $c,
                    'balance' => $a->normal_balance === 'debit' ? $d - $c : $c - $d];
        })->filter(fn ($r) => $r['debit'] != 0 || $r['credit'] != 0)->values();

        return ['rows' => $rows,
                'total_debit' => (float) $rows->sum('debit'),
                'total_credit' => (float) $rows->sum('credit')];
    }

    /** Income statement: revenue - expenses over range. */
    public function incomeStatement(int $companyId, ?string $from = null, ?string $to = null): array
    {
        return [
            'revenue'  => $this->typeBalances($companyId, 'revenue', $from, $to),
            'expenses' => $this->typeBalances($companyId, 'expense', $from, $to),
        ];
    }

    /** Balance sheet: assets, liabilities, equity as of date. */
    public function balanceSheet(int $companyId, ?string $asOf = null): array
    {
        return [
            'assets'      => $this->typeBalances($companyId, 'asset', null, $asOf),
            'liabilities' => $this->typeBalances($companyId, 'liability', null, $asOf),
            'equity'      => $this->typeBalances($companyId, 'equity', null, $asOf),
        ];
    }

    private function typeBalances(int $companyId, string $type, ?string $from, ?string $to): Collection
    {
        return ChartOfAccount::where('company_id', $companyId)->where('type', $type)
            ->where('postable', true)->get()
            ->map(function ($a) use ($companyId, $from, $to) {
                $q = GeneralLedgerEntry::where('company_id', $companyId)->where('account_id', $a->id)
                    ->when($from, fn ($x) => $x->whereDate('entry_date', '>=', $from))
                    ->when($to, fn ($x) => $x->whereDate('entry_date', '<=', $to));
                $d = (float) $q->sum('debit'); $c = (float) $q->sum('credit');
                return ['account' => $a, 'balance' => $a->normal_balance === 'debit' ? $d - $c : $c - $d];
            })->filter(fn ($r) => $r['balance'] != 0)->values();
    }

    private function rawBalance(int $accountId, ?string $from, ?string $before): float
    {
        $a = ChartOfAccount::findOrFail($accountId);
        $q = GeneralLedgerEntry::where('account_id', $accountId)
            ->when($before, fn ($x) => $x->whereDate('entry_date', '<', $before));
        $d = (float) $q->sum('debit'); $c = (float) $q->sum('credit');
        return $a->normal_balance === 'debit' ? $d - $c : $c - $d;
    }

    private function rawTotals(int $companyId, int $accountId, ?int $periodId, ?string $asOf): array
    {
        $q = GeneralLedgerEntry::where('company_id', $companyId)->where('account_id', $accountId)
            ->when($periodId, fn ($x) => $x->where('period_id', $periodId))
            ->when($asOf, fn ($x) => $x->whereDate('entry_date', '<=', $asOf));
        return [(float) $q->sum('debit'), (float) $q->sum('credit')];
    }
}
