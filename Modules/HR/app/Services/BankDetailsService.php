<?php

namespace Modules\HR\Services;

use App\Models\User;
use App\Services\AuditService;
use Illuminate\Support\Facades\DB;
use Modules\Employee\Models\Employee;
use Modules\HR\Models\EmployeeBankAccount;

class BankDetailsService
{
    public function __construct(private AuditService $audit) {}

    public function save(Employee $employee, array $data, User $actor): EmployeeBankAccount
    {
        return DB::transaction(function () use ($employee, $data, $actor) {
            if ($data['is_primary'] ?? true) {
                $employee->bankAccounts()->update(['is_primary' => false]);
            }

            $account = $employee->bankAccounts()->create(
                array_merge($data, ['verification_status' => 'pending'])
            );

            $this->audit->log('hr.bank_details.saved', $account,
                new: ['bank_name' => $account->bank_name, 'masked' => $account->maskedAccount()],
                metadata: ['actor' => $actor->id]);

            $employee->events()->create([
                'actor_user_id' => $actor->id,
                'type' => 'bank_details_changed',
                'description' => "Bank details updated ({$account->bank_name} · {$account->maskedAccount()})",
            ]);

            return $account;
        });
    }

    public function verify(EmployeeBankAccount $account, User $actor, bool $approve): EmployeeBankAccount
    {
        $account->update([
            'verification_status' => $approve ? 'verified' : 'rejected',
            'verified_by' => $actor->id,
            'verified_at' => now(),
        ]);

        $this->audit->log('hr.bank_details.verified', $account,
            new: ['status' => $account->verification_status], metadata: ['actor' => $actor->id]);

        return $account;
    }
}
