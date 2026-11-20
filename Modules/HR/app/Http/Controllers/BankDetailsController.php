<?php

namespace Modules\HR\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Employee\Models\Employee;
use Modules\HR\Models\EmployeeBankAccount;
use Modules\HR\Services\BankDetailsService;

class BankDetailsController extends Controller
{
    public function __construct(private BankDetailsService $bank) {}

    public function store(Request $request, Employee $employee)
    {
        abort_unless($request->user()->can('hr.bank_details.manage'), 403);

        $data = $request->validate([
            'account_holder_name' => 'required|string|max:150',
            'bank_name' => 'required|string|max:120',
            'bank_branch' => 'nullable|string|max:120',
            'account_number' => 'required|string|max:60',
            'iban' => 'nullable|string|max:60',
            'swift_bic' => 'nullable|string|max:20',
            'currency' => 'required|size:3',
            'country' => 'nullable|size:2',
            'payment_method' => 'required|in:bank_transfer,mobile_money,cash,cheque',
            'payment_frequency' => 'required|in:weekly,biweekly,monthly',
            'is_primary' => 'nullable|boolean',
        ]);

        $this->bank->save($employee, $data, $request->user());

        return back()->with('success', 'Bank details saved — pending verification.');
    }

    public function verify(Request $request, EmployeeBankAccount $account)
    {
        abort_unless($request->user()->can('hr.bank_details.verify'), 403);
        $this->bank->verify($account, $request->user(), $request->boolean('approve', true));
        return back()->with('success', 'Bank account verification updated.');
    }
}
