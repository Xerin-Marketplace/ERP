<?php

namespace Modules\Finance\Services;

use App\Services\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Finance\Models\ChartOfAccount;
use Modules\Finance\Models\GeneralLedgerEntry;
use Modules\Finance\Models\JournalLine;

class AccountService
{
    public function __construct(private AuditService $audit) {}

    public function create(array $data): ChartOfAccount
    {
        if (empty($data['normal_balance'])) {
            $data['normal_balance'] = in_array($data['type'], ['asset', 'expense'], true) ? 'debit' : 'credit';
        }
        if (! in_array($data['normal_balance'], ['debit', 'credit'], true)) {
            throw ValidationException::withMessages(['normal_balance' => 'Must be debit or credit.']);
        }
        $account = ChartOfAccount::create($data);
        $this->audit->log('finance.account.created', $account, new: ['code' => $account->code]);
        return $account;
    }

    /** Accounts used in journal lines cannot be deleted — deactivate instead. */
    public function deactivate(ChartOfAccount $account): ChartOfAccount
    {
        $account->update(['is_active' => false]);
        $this->audit->log('finance.account.deactivated', $account);
        return $account;
    }

    public function assertUnused(ChartOfAccount $account): void
    {
        if (JournalLine::where('account_id', $account->id)->exists()
            || GeneralLedgerEntry::where('account_id', $account->id)->exists()) {
            throw ValidationException::withMessages(['account' => 'Account is used in transactions; deactivate it instead of deleting.']);
        }
    }

    /** Resolve a mapped default account for a company (ar, ap, cash, tax_payable...). */
    public function mapped(int $companyId, string $key): ChartOfAccount
    {
        $accountId = DB::table('account_mappings')
            ->where(['company_id' => $companyId, 'key' => $key])->value('account_id');
        if (! $accountId) {
            throw ValidationException::withMessages(['mapping' => "No account mapping [{$key}] configured for company {$companyId}."]);
        }
        return ChartOfAccount::findOrFail($accountId);
    }

    public function map(int $companyId, string $key, int $accountId): void
    {
        DB::table('account_mappings')->updateOrInsert(
            ['company_id' => $companyId, 'key' => $key],
            ['account_id' => $accountId, 'updated_at' => now(), 'created_at' => now()]
        );
    }

    /** Balance from posted GL only. Positive per normal balance. */
    public function balance(ChartOfAccount $account, ?string $asOf = null): float
    {
        $q = GeneralLedgerEntry::where('account_id', $account->id);
        if ($asOf) $q->whereDate('entry_date', '<=', $asOf);
        $debit = (float) $q->sum('debit');
        $credit = (float) $q->sum('credit');
        return $account->normal_balance === 'debit' ? $debit - $credit : $credit - $debit;
    }
}
