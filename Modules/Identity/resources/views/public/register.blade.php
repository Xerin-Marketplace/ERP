<x-guest-layout>
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-900">Apply for ERP access</h1>
        <p class="mt-1 text-sm text-gray-500">Submit your application — the IT Department will review it before any account is created.</p>
    </div>

    <form method="POST" action="{{ route('register') }}" enctype="multipart/form-data" class="space-y-5">
        @csrf

        <!-- Personal details -->
        <h2 class="pt-1 text-xs font-semibold uppercase tracking-wider text-gray-400">Personal details</h2>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <x-input-label for="first_name" :value="__('First name')" />
                <x-text-input id="first_name" name="first_name" type="text" class="mt-1.5 block w-full" :value="old('first_name')" required autofocus placeholder="Amina" />
                <x-input-error :messages="$errors->get('first_name')" class="mt-1" />
            </div>
            <div>
                <x-input-label for="middle_name" :value="__('Middle name')" />
                <x-text-input id="middle_name" name="middle_name" type="text" class="mt-1.5 block w-full" :value="old('middle_name')" placeholder="Optional" />
                <x-input-error :messages="$errors->get('middle_name')" class="mt-1" />
            </div>
            <div>
                <x-input-label for="last_name" :value="__('Last name')" />
                <x-text-input id="last_name" name="last_name" type="text" class="mt-1.5 block w-full" :value="old('last_name')" required placeholder="Mwalimu" />
                <x-input-error :messages="$errors->get('last_name')" class="mt-1" />
            </div>
        </div>

        <div>
            <x-input-label for="email" :value="__('Email address')" />
            <x-text-input id="email" name="email" type="email" class="mt-1.5 block w-full" :value="old('email')" required placeholder="name@example.com" />
            <x-input-error :messages="$errors->get('email')" class="mt-1" />
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <x-input-label for="phone" :value="__('Phone')" />
                <x-text-input id="phone" name="phone" type="text" class="mt-1.5 block w-full" :value="old('phone')" required placeholder="+255712345678" />
                <x-input-error :messages="$errors->get('phone')" class="mt-1" />
            </div>
            <div>
                <x-input-label for="alt_phone" :value="__('Alternative phone')" />
                <x-text-input id="alt_phone" name="alt_phone" type="text" class="mt-1.5 block w-full" :value="old('alt_phone')" placeholder="Optional" />
                <x-input-error :messages="$errors->get('alt_phone')" class="mt-1" />
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <x-input-label for="date_of_birth" :value="__('Date of birth')" />
                <x-text-input id="date_of_birth" name="date_of_birth" type="date" class="mt-1.5 block w-full" :value="old('date_of_birth')" />
                <x-input-error :messages="$errors->get('date_of_birth')" class="mt-1" />
            </div>
            <div>
                <x-input-label for="photo" :value="__('Profile photo')" />
                <input id="photo" name="photo" type="file" accept="image/*" class="mt-1.5 block w-full text-sm text-gray-500 file:mr-3 file:rounded-md file:border-0 file:bg-gray-100 file:px-3 file:py-2 file:text-sm file:font-medium hover:file:bg-gray-200" />
                <x-input-error :messages="$errors->get('photo')" class="mt-1" />
            </div>
        </div>

        <div>
            <x-input-label for="residential_address" :value="__('Residential address')" />
            <x-text-input id="residential_address" name="residential_address" type="text" class="mt-1.5 block w-full" :value="old('residential_address')" placeholder="Street, ward, district" />
            <x-input-error :messages="$errors->get('residential_address')" class="mt-1" />
        </div>

        <!-- Employment request -->
        <div class="border-t border-gray-200 pt-6"></div>
        <h2 class="text-xs font-semibold uppercase tracking-wider text-gray-400">Employment request</h2>
        <p class="-mt-3 text-xs text-gray-400">These are requests only — IT verifies and assigns the final placement.</p>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <x-input-label for="employee_number" :value="__('Employee number (if assigned)')" />
                <x-text-input id="employee_number" name="employee_number" type="text" class="mt-1.5 block w-full" :value="old('employee_number')" placeholder="Optional" />
                <x-input-error :messages="$errors->get('employee_number')" class="mt-1" />
            </div>
            <div>
                <x-input-label for="employment_type" :value="__('Employment type')" />
                <select id="employment_type" name="employment_type" class="mt-1.5 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Select…</option>
                    @foreach (['permanent', 'contract', 'temporary', 'intern', 'consultant'] as $t)
                        <option value="{{ $t }}" @selected(old('employment_type') === $t)>{{ ucfirst($t) }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('employment_type')" class="mt-1" />
            </div>
        </div>

        <div>
            <x-input-label for="job_title" :value="__('Position requested')" />
            <x-text-input id="job_title" name="job_title" type="text" class="mt-1.5 block w-full" :value="old('job_title')" placeholder="e.g. Sales Officer" />
            <x-input-error :messages="$errors->get('job_title')" class="mt-1" />
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4" x-data="{
            company: '{{ old('requested_company_id') }}',
            branches: @js($branches->map(fn($b) => ['id' => $b->id, 'name' => $b->name, 'company_id' => $b->company_id])),
            departments: @js($departments->map(fn($d) => ['id' => $d->id, 'name' => $d->name, 'company_id' => $d->company_id, 'branch_id' => $d->branch_id])),
            branch: '{{ old('requested_branch_id') }}',
            get filteredBranches() { return this.branches.filter(b => !this.company || b.company_id == this.company) },
            get filteredDepartments() { return this.departments.filter(d => !this.company || d.company_id == this.company) }
        }">
            <div>
                <x-input-label for="requested_company_id" :value="__('Company')" />
                <select id="requested_company_id" name="requested_company_id" x-model="company" class="mt-1.5 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Select…</option>
                    @foreach ($companies as $c)
                        <option value="{{ $c->id }}" @selected(old('requested_company_id') == $c->id)>{{ $c->trading_name ?: $c->legal_name }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('requested_company_id')" class="mt-1" />
            </div>
            <div>
                <x-input-label for="requested_branch_id" :value="__('Branch')" />
                <select id="requested_branch_id" name="requested_branch_id" x-model="branch" class="mt-1.5 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Select…</option>
                    <template x-for="b in filteredBranches" :key="b.id">
                        <option :value="b.id" x-text="b.name" :selected="b.id == branch"></option>
                    </template>
                </select>
                <x-input-error :messages="$errors->get('requested_branch_id')" class="mt-1" />
            </div>
            <div>
                <x-input-label for="requested_department_id" :value="__('Department')" />
                <select id="requested_department_id" name="requested_department_id" class="mt-1.5 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Select…</option>
                    <template x-for="d in filteredDepartments" :key="d.id">
                        <option :value="d.id" x-text="d.name"></option>
                    </template>
                </select>
                <x-input-error :messages="$errors->get('requested_department_id')" class="mt-1" />
            </div>
        </div>

        <div>
            <x-input-label for="employment_start_date" :value="__('Start date (if known)')" />
            <x-text-input id="employment_start_date" name="employment_start_date" type="date" class="mt-1.5 block w-full sm:w-1/2" :value="old('employment_start_date')" />
            <x-input-error :messages="$errors->get('employment_start_date')" class="mt-1" />
        </div>

        <div>
            <x-input-label for="registration_reason" :value="__('Reason for registration')" />
            <textarea id="registration_reason" name="registration_reason" rows="3" class="mt-1.5 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Why are you requesting ERP access?">{{ old('registration_reason') }}</textarea>
            <x-input-error :messages="$errors->get('registration_reason')" class="mt-1" />
        </div>

        <!-- Documents -->
        <div class="border-t border-gray-200 pt-6"></div>
        <h2 class="text-xs font-semibold uppercase tracking-wider text-gray-400">Documents</h2>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <x-input-label for="employment_id_document" :value="__('Employment ID / letter')" />
                <input id="employment_id_document" name="employment_id_document" type="file" accept=".pdf,.jpg,.jpeg,.png" class="mt-1.5 block w-full text-sm text-gray-500 file:mr-3 file:rounded-md file:border-0 file:bg-gray-100 file:px-3 file:py-2 file:text-sm file:font-medium hover:file:bg-gray-200" />
                <x-input-error :messages="$errors->get('employment_id_document')" class="mt-1" />
            </div>
            <div>
                <x-input-label for="supporting_document" :value="__('Supporting document')" />
                <input id="supporting_document" name="supporting_document" type="file" accept=".pdf,.jpg,.jpeg,.png" class="mt-1.5 block w-full text-sm text-gray-500 file:mr-3 file:rounded-md file:border-0 file:bg-gray-100 file:px-3 file:py-2 file:text-sm file:font-medium hover:file:bg-gray-200" />
                <x-input-error :messages="$errors->get('supporting_document')" class="mt-1" />
            </div>
        </div>

        <button type="submit" class="w-full rounded-md bg-gray-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-gray-900 focus:ring-offset-2 mt-6">
            Submit application
        </button>
    </form>

    <div class="mt-8 pt-6 border-t border-gray-200 text-center text-sm space-y-2">
        <p class="text-gray-500">Already applied? <a href="{{ route('register.track') }}" class="font-medium text-indigo-600 hover:text-indigo-500">Track your application</a></p>
        <p class="text-gray-500">Have an account? <a href="{{ route('login') }}" class="font-medium text-indigo-600 hover:text-indigo-500">Sign in</a></p>
    </div>
</x-guest-layout>
