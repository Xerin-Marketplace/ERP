<x-app-layout>
    <x-slot name="header">{{ $employee->full_name }} <span class="text-sm font-normal text-gray-400">· {{ $employee->employee_number }}</span></x-slot>

    @php
        $tabs = [
            'overview' => 'Overview', 'personal' => 'Personal', 'employment' => 'Employment',
            'assignment' => 'Assignment', 'bank' => 'Bank', 'compensation' => 'Compensation',
            'documents' => 'Documents', 'history' => 'History',
            'reporting' => 'Reporting', 'onboarding' => 'Onboarding', 'activity' => 'Activity',
        ];
    @endphp

    @php
        $initials = strtoupper(mb_substr($employee->personal?->first_name ?? 'E', 0, 1).mb_substr($employee->personal?->last_name ?? '', 0, 1));
        $statusStyle = [
            'active' => 'bg-green-100 text-green-700 ring-green-200',
            'onboarding' => 'bg-blue-100 text-blue-700 ring-blue-200',
            'on_probation' => 'bg-indigo-100 text-indigo-700 ring-indigo-200',
            'on_leave' => 'bg-purple-100 text-purple-700 ring-purple-200',
            'suspended' => 'bg-amber-100 text-amber-700 ring-amber-200',
            'resigned' => 'bg-gray-100 text-gray-600 ring-gray-200',
            'terminated' => 'bg-red-100 text-red-700 ring-red-200',
            'pending_approval' => 'bg-sky-100 text-sky-700 ring-sky-200',
        ][$employee->status] ?? 'bg-gray-100 text-gray-500 ring-gray-200';
        $dotColor = [
            'active' => 'bg-green-500', 'onboarding' => 'bg-blue-500', 'on_probation' => 'bg-indigo-500',
            'on_leave' => 'bg-purple-500', 'suspended' => 'bg-amber-500', 'resigned' => 'bg-gray-400',
            'terminated' => 'bg-red-500', 'pending_approval' => 'bg-sky-500',
        ][$employee->status] ?? 'bg-gray-400';
    @endphp

    {{-- Profile header card --}}
    <div class="mb-5 rounded-sm border border-gray-200 bg-white shadow-sm overflow-hidden">
        <div class="h-1.5 bg-gradient-to-r from-xerin-600 via-xerin-500 to-xerin-400"></div>
        <div class="flex flex-wrap items-center gap-4 p-5">
            <span class="flex h-14 w-14 items-center justify-center rounded-full bg-xerin-100 text-lg font-bold text-xerin-700 ring-2 ring-xerin-200 shrink-0">{{ $initials }}</span>
            <div class="min-w-0">
                <div class="flex items-center gap-2 flex-wrap">
                    <h2 class="text-lg font-bold text-gray-900 truncate">{{ $employee->full_name }}</h2>
                    <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 {{ $statusStyle }}">
                        <i class="w-1.5 h-1.5 rounded-full {{ $dotColor }}"></i>{{ ucwords(str_replace('_', ' ', $employee->status)) }}
                    </span>
                </div>
                <p class="mt-0.5 text-xs text-gray-500">
                    <span class="font-mono">{{ $employee->employee_number }}</span>
                    <span class="mx-1.5 text-gray-300">·</span>{{ $employee->position?->title ?? 'No position' }}
                    <span class="mx-1.5 text-gray-300">·</span>{{ $employee->branch?->name ?? '—' }} / {{ $employee->department?->name ?? '—' }}
                </p>
            </div>
            <div class="ml-auto flex items-center gap-2">
                @can('employee.update')
                    <a href="{{ route('employee.edit', $employee) }}"
                       class="rounded-sm border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 transition">Edit Profile</a>
                @endcan
                @canany(['employee.update', 'employee.suspend', 'employee.terminate', 'employee.approve'])
                    <div x-data="{ open: false }" class="relative">
                        <button @click="open = !open"
                                class="inline-flex items-center gap-1.5 rounded-sm bg-xerin-600 px-4 py-2 text-sm font-semibold text-white hover:bg-xerin-700 transition">
                            Change Status
                            <svg class="w-3.5 h-3.5 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                            </svg>
                        </button>
                        <div x-show="open" x-cloak @click.away="open = false"
                             x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
                             class="absolute right-0 z-20 mt-2 w-80 rounded-sm border border-gray-200 bg-white p-4 shadow-xl">
                            <form method="POST" action="{{ route('employee.lifecycle.transition', $employee) }}">
                                @csrf
                                <label class="block text-xs font-medium text-gray-500 mb-1">New status</label>
                                <select name="status" required class="w-full rounded-sm border-gray-300 text-sm mb-2">
                                    @foreach (['active','on_probation','on_leave','suspended','resigned','terminated','pending_approval'] as $s)
                                        <option value="{{ $s }}" @if($employee->status === $s) disabled @endif>{{ ucwords(str_replace('_',' ',$s)) }}</option>
                                    @endforeach
                                </select>
                                <label class="block text-xs font-medium text-gray-500 mb-1">Reason (required, audited)</label>
                                <textarea name="reason" required rows="2" class="w-full rounded-sm border-gray-300 text-sm mb-2"></textarea>
                                <label class="block text-xs font-medium text-gray-500 mb-1">Effective date</label>
                                <input type="date" name="effective_at" class="w-full rounded-sm border-gray-300 text-sm mb-3">
                                <button type="submit" class="w-full rounded-sm bg-xerin-600 px-3 py-2 text-sm font-semibold text-white hover:bg-xerin-700">Apply Transition</button>
                            </form>
                        </div>
                    </div>
                @endcanany
            </div>
        </div>
    </div>

    {{-- Tab bar --}}
    <div class="mb-4 flex flex-wrap gap-1 rounded-sm border border-gray-200 bg-white p-1.5 shadow-sm overflow-x-auto">
        @foreach ($tabs as $key => $label)
            <a href="{{ route('employee.show', [$employee, 'tab' => $key]) }}"
               class="rounded-sm px-3 py-1.5 text-sm font-medium whitespace-nowrap transition
                      {{ $tab === $key ? 'bg-xerin-600 text-white shadow-sm' : 'text-gray-500 hover:bg-gray-100 hover:text-gray-700' }}">{{ $label }}</a>
        @endforeach
    </div>

    @if ($tab === 'overview')
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="rounded-sm border border-gray-200 bg-white p-5 shadow-sm md:col-span-2">
                <h3 class="text-sm font-semibold text-gray-900 mb-3">Employment Summary</h3>
                <dl class="grid grid-cols-2 gap-x-4 gap-y-3 text-sm">
                    <div><dt class="text-gray-500">Status</dt><dd class="font-medium">{{ ucfirst($employee->status) }}</dd></div>
                    <div><dt class="text-gray-500">Type</dt><dd class="font-medium">{{ ucwords(str_replace('_',' ',$employee->employment_type)) }}</dd></div>
                    <div><dt class="text-gray-500">Company</dt><dd class="font-medium">{{ $employee->company?->legal_name ?? '—' }}</dd></div>
                    <div><dt class="text-gray-500">Branch</dt><dd class="font-medium">{{ $employee->branch?->name ?? '—' }}</dd></div>
                    <div><dt class="text-gray-500">Department</dt><dd class="font-medium">{{ $employee->department?->name ?? '—' }}</dd></div>
                    <div><dt class="text-gray-500">Position</dt><dd class="font-medium">{{ $employee->position?->title ?? '—' }}</dd></div>
                    <div><dt class="text-gray-500">Grade</dt><dd class="font-medium">{{ $employee->position?->grade?->name ?? '—' }}</dd></div>
                    <div><dt class="text-gray-500">Manager</dt><dd class="font-medium">{{ $employee->manager?->full_name ?? '—' }}</dd></div>
                    <div><dt class="text-gray-500">Start Date</dt><dd class="font-medium">{{ $employee->start_date?->toFormattedDateString() ?? '—' }}</dd></div>
                    <div><dt class="text-gray-500">Contract End</dt><dd class="font-medium">{{ $employee->contract_end_date?->toFormattedDateString() ?? '—' }}</dd></div>
                </dl>
            </div>
            <div class="space-y-4">
                <div class="rounded-sm border border-gray-200 bg-white p-5 shadow-sm">
                    <h3 class="text-sm font-semibold text-gray-900 mb-2">ERP Account</h3>
                    @if ($employee->user)
                        <p class="text-sm">{{ $employee->user->email }}</p>
                        <p class="text-xs text-gray-400">Status: {{ $employee->user->status->value }}</p>
                        <p class="text-xs text-gray-400">Roles: {{ $employee->user->roles->pluck('name')->join(', ') ?: '—' }}</p>
                    @else
                        <p class="text-sm text-gray-400">No linked ERP account.</p>
                    @endif
                </div>
                <div class="rounded-sm border border-gray-200 bg-white p-5 shadow-sm">
                    <h3 class="text-sm font-semibold text-gray-900 mb-2">Onboarding</h3>
                    @if ($employee->onboarding)
                        <div class="h-2 rounded bg-gray-100 overflow-hidden"><div class="h-2 bg-xerin-600" style="width:{{ $employee->onboarding->progress() }}%"></div></div>
                        <p class="mt-1 text-xs text-gray-500">{{ $employee->onboarding->progress() }}% · {{ ucfirst(str_replace('_',' ',$employee->onboarding->status)) }}</p>
                    @else
                        <p class="text-sm text-gray-400">Not started.</p>
                    @endif
                </div>
            </div>
        </div>

    @elseif ($tab === 'personal')
        <div class="rounded-sm border border-gray-200 bg-white p-5 shadow-sm">
            <h3 class="text-sm font-semibold text-gray-900 mb-3">Personal Details</h3>
            <dl class="grid grid-cols-2 sm:grid-cols-3 gap-x-4 gap-y-3 text-sm">
                <div><dt class="text-gray-500">DOB</dt><dd>{{ $employee->personal?->date_of_birth?->toFormattedDateString() ?? '—' }}</dd></div>
                <div><dt class="text-gray-500">Gender</dt><dd>{{ ucfirst($employee->personal?->gender ?? '—') }}</dd></div>
                <div><dt class="text-gray-500">Nationality</dt><dd>{{ $employee->personal?->nationality ?? '—' }}</dd></div>
                <div><dt class="text-gray-500">National ID</dt><dd>{{ $employee->personal?->national_id ?? '—' }}</dd></div>
                <div><dt class="text-gray-500">Personal Email</dt><dd>{{ $employee->personal?->personal_email ?? '—' }}</dd></div>
                <div><dt class="text-gray-500">Phone</dt><dd>{{ $employee->personal?->phone ?? '—' }}</dd></div>
                <div class="col-span-2"><dt class="text-gray-500">Address</dt><dd>{{ $employee->personal?->residential_address ?? '—' }}</dd></div>
            </dl>
            <h4 class="text-sm font-semibold text-gray-900 mt-6 mb-2">Emergency Contacts</h4>
            <ul class="text-sm space-y-1">
                @forelse ($employee->emergencyContacts as $c)
                    <li>{{ $c->name }} <span class="text-gray-400">({{ $c->relationship }})</span> — {{ $c->phone }}</li>
                @empty
                    <li class="text-gray-400">None recorded.</li>
                @endforelse
            </ul>
        </div>

    @elseif ($tab === 'employment')
        <div class="rounded-sm border border-gray-200 bg-white shadow-sm overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-xs uppercase text-gray-500"><tr>
                    <th class="px-4 py-3 text-left">Type</th><th class="px-4 py-3 text-left">Reference</th>
                    <th class="px-4 py-3 text-left">Start</th><th class="px-4 py-3 text-left">End</th>
                    <th class="px-4 py-3 text-left">Confirmation</th><th class="px-4 py-3 text-left">Status</th>
                </tr></thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($employee->employmentRecords as $r)
                        <tr>
                            <td class="px-4 py-3">{{ ucfirst($r->type) }}</td>
                            <td class="px-4 py-3 font-mono text-xs">{{ $r->contract_reference ?? '—' }}</td>
                            <td class="px-4 py-3">{{ $r->start_date?->toDateString() ?? '—' }}</td>
                            <td class="px-4 py-3">{{ $r->end_date?->toDateString() ?? '—' }}</td>
                            <td class="px-4 py-3">{{ ucfirst($r->confirmation_status) }}</td>
                            <td class="px-4 py-3"><span class="rounded-full px-2 py-0.5 text-xs {{ $r->status === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">{{ ucfirst($r->status) }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400">No employment records.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    @elseif ($tab === 'assignment')
        <div class="rounded-sm border border-gray-200 bg-white p-5 shadow-sm">
            <h3 class="text-sm font-semibold text-gray-900 mb-3">Current Placement</h3>
            <p class="text-sm">{{ $employee->company?->legal_name ?? '—' }} → {{ $employee->branch?->name ?? '—' }} → {{ $employee->department?->name ?? '—' }} → {{ $employee->position?->title ?? '—' }}</p>
            <h4 class="text-sm font-semibold text-gray-900 mt-6 mb-2">IAM Assignment History <span class="text-xs font-normal text-gray-400">(Identity module)</span></h4>
            <ul class="text-sm space-y-2">
                @forelse ($employee->user?->assignments ?? [] as $a)
                    <li class="border-l-2 {{ $a->effective_end ? 'border-gray-200' : 'border-xerin-500' }} pl-3">
                        {{ $a->company?->legal_name }} / {{ $a->branch?->name }} / {{ $a->department?->name }}
                        <span class="text-xs text-gray-400">{{ $a->effective_start?->toDateString() }} → {{ $a->effective_end?->toDateString() ?? 'current' }} · {{ $a->assignment_type }}</span>
                    </li>
                @empty
                    <li class="text-gray-400">No IAM assignment history (no linked account).</li>
                @endforelse
            </ul>
        </div>

    @elseif ($tab === 'bank')
        <div class="rounded-sm border border-gray-200 bg-white p-5 shadow-sm">
            <div class="flex justify-between items-center mb-3">
                <h3 class="text-sm font-semibold text-gray-900">Bank & Payment Details</h3>
                @can('hr.bank_details.manage')
                    <span class="text-xs text-gray-400">Account numbers are encrypted & masked</span>
                @endcan
            </div>
            @forelse ($employee->bankAccounts as $acc)
                <div class="flex flex-wrap items-center justify-between py-2.5 border-b last:border-0 text-sm">
                    <div>
                        <p class="font-medium">{{ $acc->bank_name }} · {{ $acc->maskedAccount() }}</p>
                        <p class="text-xs text-gray-500">{{ $acc->account_holder_name }} · {{ $acc->currency }} · {{ ucwords(str_replace('_',' ',$acc->payment_method)) }} {{ $acc->is_primary ? '· primary' : '' }}</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="text-xs rounded-full px-2 py-0.5 {{ $acc->verification_status==='verified' ? 'bg-green-100 text-green-700' : ($acc->verification_status==='rejected' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700') }}">{{ ucfirst($acc->verification_status) }}</span>
                        @can('hr.bank_details.verify')
                            @if ($acc->verification_status === 'pending')
                                <form method="POST" action="{{ route('hr.bank.verify', $acc) }}">@csrf
                                    <input type="hidden" name="approve" value="1">
                                    <button class="text-xs font-semibold text-green-700">Verify</button></form>
                            @endif
                        @endcan
                    </div>
                </div>
            @empty
                <p class="text-sm text-gray-400">No bank details on file.</p>
            @endforelse
            @can('hr.bank_details.manage')
                <details class="mt-4">
                    <summary class="text-sm font-semibold text-xerin-700 cursor-pointer">+ Add bank account</summary>
                    <form method="POST" action="{{ route('hr.bank.store', $employee) }}" class="mt-3 grid grid-cols-1 sm:grid-cols-3 gap-3">
                        @csrf
                        <input name="account_holder_name" required placeholder="Account holder" class="rounded-sm border-gray-300 text-sm">
                        <input name="bank_name" required placeholder="Bank name" class="rounded-sm border-gray-300 text-sm">
                        <input name="bank_branch" placeholder="Branch" class="rounded-sm border-gray-300 text-sm">
                        <input name="account_number" required placeholder="Account number" class="rounded-sm border-gray-300 text-sm">
                        <input name="iban" placeholder="IBAN (if applicable)" class="rounded-sm border-gray-300 text-sm">
                        <input name="swift_bic" placeholder="SWIFT/BIC" class="rounded-sm border-gray-300 text-sm">
                        <select name="currency" class="rounded-sm border-gray-300 text-sm"><option>TZS</option><option>AED</option><option>USD</option></select>
                        <select name="country" class="rounded-sm border-gray-300 text-sm"><option value="TZ">Tanzania</option><option value="AE">UAE</option></select>
                        <select name="payment_method" class="rounded-sm border-gray-300 text-sm"><option value="bank_transfer">Bank transfer</option><option value="mobile_money">Mobile money</option><option value="cash">Cash</option><option value="cheque">Cheque</option></select>
                        <input type="hidden" name="payment_frequency" value="monthly">
                        <button class="rounded-sm bg-xerin-600 px-4 py-2 text-sm font-semibold text-white sm:col-span-3">Save — pending verification</button>
                    </form>
                </details>
            @endcan
        </div>

    @elseif ($tab === 'compensation')
        <div class="rounded-sm border border-gray-200 bg-white p-5 shadow-sm">
            <h3 class="text-sm font-semibold text-gray-900 mb-3">Compensation & Salary</h3>
            @can('hr.compensation.view')
                @php $current = $employee->compensations->where('status','approved')->first(); @endphp
                @if ($current)
                    <dl class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-sm mb-4">
                        <div><dt class="text-gray-500">Basic</dt><dd class="font-medium">{{ $current->currency }} {{ number_format($current->basic_salary,2) }}</dd></div>
                        <div><dt class="text-gray-500">Gross</dt><dd class="font-medium">{{ $current->currency }} {{ number_format($current->gross(),2) }}</dd></div>
                        <div><dt class="text-gray-500">Deductions</dt><dd class="font-medium">{{ $current->currency }} {{ number_format($current->deductions(),2) }}</dd></div>
                        <div><dt class="text-gray-500">Net</dt><dd class="font-semibold text-green-700">{{ $current->currency }} {{ number_format($current->net(),2) }}</dd></div>
                    </dl>
                    <p class="text-xs text-gray-400 mb-4">Effective {{ $current->effective_date->format('M j, Y') }} · approved by {{ $current->approver->name ?? '—' }}</p>
                @else
                    <p class="text-sm text-gray-400 mb-4">No approved compensation package.</p>
                @endif

                <h4 class="text-xs font-semibold uppercase text-gray-500 mb-2">Revision history</h4>
                @forelse ($employee->compensations as $rev)
                    <div class="flex justify-between py-2 border-b last:border-0 text-sm">
                        <span>{{ $rev->currency }} {{ number_format($rev->gross(),2) }} gross · effective {{ $rev->effective_date->format('M j, Y') }}</span>
                        <span class="text-xs {{ $rev->status==='approved' ? 'text-green-600' : ($rev->status==='pending' ? 'text-amber-600' : 'text-gray-400') }}">{{ ucfirst($rev->status) }}</span>
                    </div>
                @empty <p class="text-sm text-gray-400">No history.</p> @endforelse
            @else
                <p class="text-sm text-gray-400">You don't have permission to view compensation details.</p>
            @endcan

            @can('hr.compensation.manage')
                <details class="mt-4">
                    <summary class="text-sm font-semibold text-xerin-700 cursor-pointer">+ Propose salary revision</summary>
                    <form method="POST" action="{{ route('hr.compensation.store', $employee) }}" class="mt-3 grid grid-cols-2 sm:grid-cols-4 gap-3">
                        @csrf
                        <input name="basic_salary" type="number" step="0.01" required placeholder="Basic *" class="rounded-sm border-gray-300 text-sm">
                        <input name="housing_allowance" type="number" step="0.01" placeholder="Housing" class="rounded-sm border-gray-300 text-sm">
                        <input name="transport_allowance" type="number" step="0.01" placeholder="Transport" class="rounded-sm border-gray-300 text-sm">
                        <input name="medical_allowance" type="number" step="0.01" placeholder="Medical" class="rounded-sm border-gray-300 text-sm">
                        <input name="communication_allowance" type="number" step="0.01" placeholder="Communication" class="rounded-sm border-gray-300 text-sm">
                        <input name="meal_allowance" type="number" step="0.01" placeholder="Meal" class="rounded-sm border-gray-300 text-sm">
                        <input name="other_allowances" type="number" step="0.01" placeholder="Other allowances" class="rounded-sm border-gray-300 text-sm">
                        <input name="overtime_rate" type="number" step="0.01" placeholder="OT rate/hr" class="rounded-sm border-gray-300 text-sm">
                        <input name="pension_deduction" type="number" step="0.01" placeholder="Pension" class="rounded-sm border-gray-300 text-sm">
                        <input name="insurance_deduction" type="number" step="0.01" placeholder="Insurance" class="rounded-sm border-gray-300 text-sm">
                        <input name="tax_deduction" type="number" step="0.01" placeholder="Tax" class="rounded-sm border-gray-300 text-sm">
                        <input name="other_deductions" type="number" step="0.01" placeholder="Other deductions" class="rounded-sm border-gray-300 text-sm">
                        <select name="currency" class="rounded-sm border-gray-300 text-sm"><option>TZS</option><option>AED</option><option>USD</option></select>
                        <select name="pay_frequency" class="rounded-sm border-gray-300 text-sm"><option>monthly</option><option>biweekly</option><option>weekly</option></select>
                        <input type="date" name="effective_date" required class="rounded-sm border-gray-300 text-sm">
                        <input name="reason" required placeholder="Reason *" class="rounded-sm border-gray-300 text-sm">
                        <button class="rounded-sm bg-xerin-600 px-4 py-2 text-sm font-semibold text-white col-span-2 sm:col-span-4">Propose — needs approval</button>
                    </form>
                </details>
            @endcan
        </div>

    @elseif ($tab === 'documents')
        <div class="rounded-sm border border-gray-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-semibold text-gray-900">Documents ({{ $employee->documents_count }})</h3>
                <a href="{{ route('employee.documents.index', $employee) }}" class="text-sm font-semibold text-xerin-700 hover:underline">Manage →</a>
            </div>
            <ul class="text-sm divide-y divide-gray-100">
                @forelse ($employee->documents as $doc)
                    <li class="py-2 flex justify-between">
                        <span>{{ $doc->title }} <span class="text-xs text-gray-400">v{{ $doc->version }} · {{ $doc->category->name }}</span></span>
                        <a href="{{ route('employee.documents.download', $doc) }}" class="text-xerin-700 text-xs font-semibold">Download</a>
                    </li>
                @empty
                    <li class="py-4 text-gray-400">No documents.</li>
                @endforelse
            </ul>
        </div>

    @elseif ($tab === 'history')
        <div class="rounded-sm border border-gray-200 bg-white p-5 shadow-sm space-y-6">
            <div>
                <h3 class="text-sm font-semibold text-gray-900 mb-2">Transfers</h3>
                <ul class="text-sm divide-y divide-gray-100">
                    @forelse ($employee->transfers as $t)
                        <li class="py-2 flex justify-between">
                            <span>{{ ucfirst($t->type) }} → {{ $t->toCompany?->legal_name ?? $t->toBranch?->name ?? '—' }}
                                <span class="text-xs text-gray-400">by {{ $t->requester?->name }}</span></span>
                            <span class="text-xs font-medium {{ $t->status === 'approved' ? 'text-green-600' : ($t->status === 'rejected' ? 'text-red-600' : 'text-amber-600') }}">{{ ucfirst($t->status) }}</span>
                        </li>
                    @empty <li class="py-2 text-gray-400">None.</li> @endforelse
                </ul>
            </div>
            <div>
                <h3 class="text-sm font-semibold text-gray-900 mb-2">Employment Changes</h3>
                <ul class="text-sm divide-y divide-gray-100">
                    @forelse ($employee->changeRequests as $c)
                        <li class="py-2 flex justify-between">
                            <span>{{ ucwords(str_replace('_',' ',$c->type)) }} <span class="text-xs text-gray-400">by {{ $c->initiator?->name }}</span></span>
                            <span class="text-xs font-medium {{ $c->status === 'approved' ? 'text-green-600' : ($c->status === 'rejected' ? 'text-red-600' : 'text-amber-600') }}">{{ ucfirst($c->status) }}</span>
                        </li>
                    @empty <li class="py-2 text-gray-400">None.</li> @endforelse
                </ul>
            </div>
        </div>

    @elseif ($tab === 'reporting')
        <div class="rounded-sm border border-gray-200 bg-white p-5 shadow-sm">
            <h3 class="text-sm font-semibold text-gray-900 mb-2">Reports To</h3>
            <p class="text-sm mb-4">{{ $employee->manager?->full_name ?? '—' }}</p>
            <h3 class="text-sm font-semibold text-gray-900 mb-2">Direct Reports</h3>
            <ul class="text-sm space-y-1">
                @forelse ($directReports as $r)
                    <li><a href="{{ route('employee.show', $r) }}" class="text-xerin-700 hover:underline">{{ $r->full_name }}</a>
                        <span class="text-xs text-gray-400">{{ $r->position?->title }}</span></li>
                @empty <li class="text-gray-400">None.</li> @endforelse
            </ul>
        </div>

    @elseif ($tab === 'onboarding')
        <div class="rounded-sm border border-gray-200 bg-white p-5 shadow-sm">
            @if ($employee->onboarding)
                <ul class="text-sm space-y-2">
                    @foreach ($employee->onboarding->tasks as $task)
                        <li class="flex justify-between">
                            <span class="{{ $task->status === 'completed' ? 'line-through text-gray-400' : '' }}">{{ $task->title }}</span>
                            <span class="text-xs text-gray-400">{{ ucfirst($task->status) }}</span>
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="text-sm text-gray-400">Onboarding not started.</p>
            @endif
        </div>

    @else
        <div class="rounded-sm border border-gray-200 bg-white p-5 shadow-sm">
            <h3 class="text-sm font-semibold text-gray-900 mb-3">Activity Timeline</h3>
            <ul class="text-sm space-y-3">
                @forelse ($employee->events as $ev)
                    <li class="border-l-2 border-gray-200 pl-3">
                        <p>{{ $ev->description }}</p>
                        <p class="text-xs text-gray-400">{{ $ev->created_at->diffForHumans() }} · {{ $ev->actor?->name ?? 'System' }}</p>
                    </li>
                @empty
                    <li class="text-gray-400">No activity.</li>
                @endforelse
            </ul>
        </div>
    @endif
</x-app-layout>
