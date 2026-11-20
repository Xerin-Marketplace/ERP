<x-guest-layout>
    <div class="max-w-2xl mx-auto py-10 px-4">
        <h1 class="text-xl font-bold">{{ str_replace('_', ' ', ucwords($type, '_')) }} Access Request</h1>
        <form method="POST" action="{{ route('access.store') }}" enctype="multipart/form-data" class="mt-6 space-y-5 rounded-sm border border-gray-200 bg-white p-6 shadow-sm">
            @csrf
            <input type="hidden" name="type" value="{{ $type }}">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-600">{ __('access.full_name') } { __('access.required') }</label>
                    <input name="full_name" value="{{ old('full_name') }}" required class="mt-1 w-full rounded-sm border-gray-300 text-sm">
                    @error('full_name') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600">{ __('access.email') } { __('access.required') }</label>
                    <input name="email" type="email" value="{{ old('email') }}" required class="mt-1 w-full rounded-sm border-gray-300 text-sm">
                    @error('email') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600">{ __('access.phone') }</label>
                    <input name="phone" value="{{ old('phone') }}" class="mt-1 w-full rounded-sm border-gray-300 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600">{ __('access.organization') }</label>
                    <input name="organization" value="{{ old('organization') }}" class="mt-1 w-full rounded-sm border-gray-300 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600">{ __('access.branch') }</label>
                    <select name="branch_id" class="mt-1 w-full rounded-sm border-gray-300 text-sm">
                        <option value="">—</option>
                        @foreach ($branches as $b) <option value="{{ $b->id }}" @selected(old('branch_id') == $b->id)>{{ $b->name }}</option> @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600">{ __('access.department') }</label>
                    <select name="department_id" class="mt-1 w-full rounded-sm border-gray-300 text-sm">
                        <option value="">—</option>
                        @foreach ($departments as $d) <option value="{{ $d->id }}" @selected(old('department_id') == $d->id)>{{ $d->name }}{{ $d->branch?->code ? ' ('.$d->branch->code.')' : '' }}</option> @endforeach
                    </select>
                </div>
                @if ($type !== 'external')
                <div>
                    <label class="block text-xs font-semibold text-gray-600">{ __('access.job_title') }</label>
                    <input name="job_title" value="{{ old('job_title') }}" class="mt-1 w-full rounded-sm border-gray-300 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600">{ __('access.start_date') }</label>
                    <input name="start_date" type="date" value="{{ old('start_date') }}" class="mt-1 w-full rounded-sm border-gray-300 text-sm">
                </div>
                @endif
                @if ($type === 'existing_employee')
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-gray-600">Employee ID *</label>
                    <input name="employee_id" value="{{ old('employee_id') }}" required class="mt-1 w-full rounded-sm border-gray-300 text-sm" placeholder="Your employee record ID">
                    @error('employee_id') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
                @endif
                @if ($type === 'external')
                <div>
                    <label class="block text-xs font-semibold text-gray-600">{ __('access.expiry_date') } { __('access.required') }</label>
                    <input name="expiry_date" type="date" value="{{ old('expiry_date') }}" required class="mt-1 w-full rounded-sm border-gray-300 text-sm">
                    @error('expiry_date') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600">{ __('access.sponsor') } { __('access.required') }</label>
                    <input name="sponsor_id" value="{{ old('sponsor_id') }}" required class="mt-1 w-full rounded-sm border-gray-300 text-sm">
                    @error('sponsor_id') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
                @endif
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600">{ __('access.justification') }</label>
                <textarea name="justification" rows="3" class="mt-1 w-full rounded-sm border-gray-300 text-sm">{{ old('justification') }}</textarea>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600">{{ __('access.documents') }}</label>
                <input type="file" name="documents[]" multiple accept=".pdf,.jpg,.jpeg,.png" class="mt-1 w-full text-sm">
                @error('documents.*') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <label class="flex items-start gap-2 text-xs text-gray-600">
                <input type="checkbox" name="privacy_ack" value="1" required class="mt-0.5 rounded-sm">
                {{ __('access.privacy') }}
            </label>
            @error('privacy_ack') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
            @error('type') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
            @error('external') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
            <button class="rounded-sm bg-xerin-700 px-5 py-2 text-sm font-semibold text-white hover:bg-xerin-800">{{ __('access.submit') }}</button>
        </form>
    </div>
</x-guest-layout>
