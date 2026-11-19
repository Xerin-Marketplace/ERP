<x-app-layout>
    <x-slot name="header">{{ $employee->exists ? __('Edit Employee') : __('New Employee') }}</x-slot>

    <form method="POST" action="{{ $employee->exists ? route('employee.update', $employee) : route('employee.store') }}"
          class="rounded-sm border border-gray-200 bg-white p-6 shadow-sm space-y-6">
        @csrf
        @if ($employee->exists) @method('PUT') @endif

        <section>
            <h3 class="text-sm font-semibold text-gray-900 mb-3">Personal Information</h3>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div><label class="block text-xs font-medium text-gray-500 mb-1">First Name *</label>
                    <input name="first_name" value="{{ old('first_name', $employee->personal?->first_name) }}" required class="w-full rounded-sm border-gray-300 text-sm shadow-sm"></div>
                <div><label class="block text-xs font-medium text-gray-500 mb-1">Middle Name</label>
                    <input name="middle_name" value="{{ old('middle_name', $employee->personal?->middle_name) }}" class="w-full rounded-sm border-gray-300 text-sm shadow-sm"></div>
                <div><label class="block text-xs font-medium text-gray-500 mb-1">Last Name *</label>
                    <input name="last_name" value="{{ old('last_name', $employee->personal?->last_name) }}" required class="w-full rounded-sm border-gray-300 text-sm shadow-sm"></div>
                <div><label class="block text-xs font-medium text-gray-500 mb-1">Preferred Name</label>
                    <input name="preferred_name" value="{{ old('preferred_name', $employee->personal?->preferred_name) }}" class="w-full rounded-sm border-gray-300 text-sm shadow-sm"></div>
                <div><label class="block text-xs font-medium text-gray-500 mb-1">Gender</label>
                    <select name="gender" class="w-full rounded-sm border-gray-300 text-sm shadow-sm">
                        <option value="">—</option>
                        @foreach (['male' => 'Male', 'female' => 'Female', 'other' => 'Other'] as $v => $l)
                            <option value="{{ $v }}" @selected(old('gender', $employee->personal?->gender) === $v)>{{ $l }}</option>
                        @endforeach
                    </select></div>
                <div><label class="block text-xs font-medium text-gray-500 mb-1">Date of Birth</label>
                    <input type="date" name="date_of_birth" value="{{ old('date_of_birth', $employee->personal?->date_of_birth?->toDateString()) }}" class="w-full rounded-sm border-gray-300 text-sm shadow-sm"></div>
                <div><label class="block text-xs font-medium text-gray-500 mb-1">Nationality</label>
                    <input name="nationality" value="{{ old('nationality', $employee->personal?->nationality) }}" class="w-full rounded-sm border-gray-300 text-sm shadow-sm"></div>
                <div><label class="block text-xs font-medium text-gray-500 mb-1">National ID</label>
                    <input name="national_id" value="{{ old('national_id', $employee->personal?->national_id) }}" class="w-full rounded-sm border-gray-300 text-sm shadow-sm"></div>
                <div><label class="block text-xs font-medium text-gray-500 mb-1">Personal Email</label>
                    <input type="email" name="personal_email" value="{{ old('personal_email', $employee->personal?->personal_email) }}" class="w-full rounded-sm border-gray-300 text-sm shadow-sm"></div>
                <div><label class="block text-xs font-medium text-gray-500 mb-1">Phone</label>
                    <input name="phone" value="{{ old('phone', $employee->personal?->phone) }}" class="w-full rounded-sm border-gray-300 text-sm shadow-sm"></div>
                <div><label class="block text-xs font-medium text-gray-500 mb-1">Alt Phone</label>
                    <input name="alt_phone" value="{{ old('alt_phone', $employee->personal?->alt_phone) }}" class="w-full rounded-sm border-gray-300 text-sm shadow-sm"></div>
                <div class="sm:col-span-3"><label class="block text-xs font-medium text-gray-500 mb-1">Residential Address</label>
                    <textarea name="residential_address" rows="2" class="w-full rounded-sm border-gray-300 text-sm shadow-sm">{{ old('residential_address', $employee->personal?->residential_address) }}</textarea></div>
            </div>
        </section>

        <section>
            <h3 class="text-sm font-semibold text-gray-900 mb-3">Employment</h3>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                @if (! $employee->exists)
                    <div><label class="block text-xs font-medium text-gray-500 mb-1">Employee Number</label>
                        <input name="employee_number" value="{{ old('employee_number') }}" placeholder="Auto: XERIN-EMP-YYYY-######" class="w-full rounded-sm border-gray-300 text-sm shadow-sm"></div>
                    <div><label class="block text-xs font-medium text-gray-500 mb-1">Link ERP User</label>
                        <select name="user_id" class="w-full rounded-sm border-gray-300 text-sm shadow-sm">
                            <option value="">— None —</option>
                            @foreach ($unlinkedUsers as $u)
                                <option value="{{ $u->id }}" @selected(old('user_id') == $u->id)>{{ $u->name }} ({{ $u->email }})</option>
                            @endforeach
                        </select></div>
                @endif
                <div><label class="block text-xs font-medium text-gray-500 mb-1">Employment Type *</label>
                    <select name="employment_type" required class="w-full rounded-sm border-gray-300 text-sm shadow-sm">
                        @foreach (\Modules\Employee\Models\Employee::TYPES as $t)
                            <option value="{{ $t }}" @selected(old('employment_type', $employee->employment_type ?? 'permanent') === $t)>{{ ucwords(str_replace('_', ' ', $t)) }}</option>
                        @endforeach
                    </select></div>
                <div><label class="block text-xs font-medium text-gray-500 mb-1">Category</label>
                    <input name="employee_category" value="{{ old('employee_category', $employee->employee_category) }}" class="w-full rounded-sm border-gray-300 text-sm shadow-sm"></div>
                <div><label class="block text-xs font-medium text-gray-500 mb-1">Start Date</label>
                    <input type="date" name="start_date" value="{{ old('start_date', $employee->start_date?->toDateString()) }}" class="w-full rounded-sm border-gray-300 text-sm shadow-sm"></div>
                <div><label class="block text-xs font-medium text-gray-500 mb-1">Confirmation Date</label>
                    <input type="date" name="confirmation_date" value="{{ old('confirmation_date', $employee->confirmation_date?->toDateString()) }}" class="w-full rounded-sm border-gray-300 text-sm shadow-sm"></div>
                <div><label class="block text-xs font-medium text-gray-500 mb-1">Contract End</label>
                    <input type="date" name="contract_end_date" value="{{ old('contract_end_date', $employee->contract_end_date?->toDateString()) }}" class="w-full rounded-sm border-gray-300 text-sm shadow-sm"></div>
                <div><label class="block text-xs font-medium text-gray-500 mb-1">Work Location</label>
                    <input name="work_location" value="{{ old('work_location', $employee->work_location) }}" class="w-full rounded-sm border-gray-300 text-sm shadow-sm"></div>
                <div><label class="block text-xs font-medium text-gray-500 mb-1">Official Email</label>
                    <input type="email" name="official_email" value="{{ old('official_email', $employee->official_email) }}" class="w-full rounded-sm border-gray-300 text-sm shadow-sm"></div>
                @if ($employee->exists)
                    <div><label class="block text-xs font-medium text-gray-500 mb-1">Status</label>
                        <select name="status" class="w-full rounded-sm border-gray-300 text-sm shadow-sm">
                            @foreach (['onboarding', 'active', 'suspended'] as $s)
                                <option value="{{ $s }}" @selected($employee->status === $s)>{{ ucfirst($s) }}</option>
                            @endforeach
                        </select></div>
                @else
                    <div><label class="block text-xs font-medium text-gray-500 mb-1">Status</label>
                        <select name="status" class="w-full rounded-sm border-gray-300 text-sm shadow-sm">
                            <option value="onboarding" @selected(old('status', 'onboarding') === 'onboarding')>Onboarding</option>
                            <option value="active" @selected(old('status') === 'active')>Active</option>
                        </select></div>
                @endif
            </div>
        </section>

        @if (! $employee->exists)
        <section>
            <h3 class="text-sm font-semibold text-gray-900 mb-3">Placement (validated against Organization)</h3>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div><label class="block text-xs font-medium text-gray-500 mb-1">Company</label>
                    <select name="company_id" class="w-full rounded-sm border-gray-300 text-sm shadow-sm">
                        <option value="">—</option>
                        @foreach ($companies as $c)<option value="{{ $c->id }}" @selected(old('company_id') == $c->id)>{{ $c->legal_name }}</option>@endforeach
                    </select></div>
                <div><label class="block text-xs font-medium text-gray-500 mb-1">Branch</label>
                    <select name="branch_id" class="w-full rounded-sm border-gray-300 text-sm shadow-sm">
                        <option value="">—</option>
                        @foreach ($branches as $b)<option value="{{ $b->id }}" @selected(old('branch_id') == $b->id)>{{ $b->name }}</option>@endforeach
                    </select></div>
                <div><label class="block text-xs font-medium text-gray-500 mb-1">Department</label>
                    <select name="department_id" class="w-full rounded-sm border-gray-300 text-sm shadow-sm">
                        <option value="">—</option>
                        @foreach ($departments as $d)<option value="{{ $d->id }}" @selected(old('department_id') == $d->id)>{{ $d->name }}</option>@endforeach
                    </select></div>
                <div><label class="block text-xs font-medium text-gray-500 mb-1">Position</label>
                    <select name="job_position_id" class="w-full rounded-sm border-gray-300 text-sm shadow-sm">
                        <option value="">—</option>
                        @foreach ($positions as $p)<option value="{{ $p->id }}" @selected(old('job_position_id') == $p->id)>{{ $p->title }}</option>@endforeach
                    </select></div>
                <div><label class="block text-xs font-medium text-gray-500 mb-1">Reporting Manager</label>
                    <select name="manager_employee_id" class="w-full rounded-sm border-gray-300 text-sm shadow-sm">
                        <option value="">—</option>
                        @foreach ($managers as $m)<option value="{{ $m->id }}" @selected(old('manager_employee_id') == $m->id)>{{ $m->full_name }}</option>@endforeach
                    </select></div>
            </div>
        </section>
        @endif

        <div class="flex gap-3">
            <button class="rounded-sm bg-xerin-600 px-5 py-2 text-sm font-semibold text-white hover:bg-xerin-700">{{ $employee->exists ? 'Save Changes' : 'Create Employee' }}</button>
            <a href="{{ route('employee.index') }}" class="rounded-sm border border-gray-300 px-5 py-2 text-sm font-semibold text-gray-600">Cancel</a>
        </div>
    </form>
</x-app-layout>
