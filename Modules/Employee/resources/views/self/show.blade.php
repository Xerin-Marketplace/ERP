<x-app-layout>
    <x-slot name="header">{{ __('My Employment') }}</x-slot>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="rounded-sm border border-gray-200 bg-white p-5 shadow-sm">
            <h3 class="text-sm font-semibold text-gray-900 mb-3">My Placement</h3>
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between"><dt class="text-gray-500">Employee No.</dt><dd class="font-mono text-xs">{{ $employee->employee_number }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Company</dt><dd>{{ $employee->company?->legal_name ?? '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Branch</dt><dd>{{ $employee->branch?->name ?? '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Department</dt><dd>{{ $employee->department?->name ?? '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Position</dt><dd>{{ $employee->position?->title ?? '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Manager</dt><dd>{{ $employee->manager?->full_name ?? '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Type</dt><dd>{{ ucwords(str_replace('_',' ',$employee->employment_type)) }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Start Date</dt><dd>{{ $employee->start_date?->toFormattedDateString() ?? '—' }}</dd></div>
            </dl>
            <p class="mt-3 text-xs text-gray-400">Placement changes require HR/IT approval.</p>
        </div>

        <div class="rounded-sm border border-gray-200 bg-white p-5 shadow-sm">
            <h3 class="text-sm font-semibold text-gray-900 mb-3">Update My Details</h3>
            <form method="POST" action="{{ route('employee.me.update') }}" class="space-y-3 text-sm">
                @csrf @method('PUT')
                <div><label class="block text-xs text-gray-500 mb-1">Preferred Name</label>
                    <input name="preferred_name" value="{{ old('preferred_name', $employee->personal?->preferred_name) }}" class="w-full rounded-sm border-gray-300 text-sm"></div>
                <div><label class="block text-xs text-gray-500 mb-1">Phone</label>
                    <input name="phone" value="{{ old('phone', $employee->personal?->phone) }}" class="w-full rounded-sm border-gray-300 text-sm"></div>
                <div><label class="block text-xs text-gray-500 mb-1">Alternative Phone</label>
                    <input name="alt_phone" value="{{ old('alt_phone', $employee->personal?->alt_phone) }}" class="w-full rounded-sm border-gray-300 text-sm"></div>
                <div><label class="block text-xs text-gray-500 mb-1">Personal Email</label>
                    <input type="email" name="personal_email" value="{{ old('personal_email', $employee->personal?->personal_email) }}" class="w-full rounded-sm border-gray-300 text-sm"></div>
                <div><label class="block text-xs text-gray-500 mb-1">Address</label>
                    <textarea name="residential_address" rows="2" class="w-full rounded-sm border-gray-300 text-sm">{{ old('residential_address', $employee->personal?->residential_address) }}</textarea></div>
                <button class="rounded-sm bg-xerin-600 px-4 py-2 text-sm font-semibold text-white">Save</button>
            </form>
        </div>

        <div class="space-y-4">
            @if ($employee->onboarding)
            <div class="rounded-sm border border-gray-200 bg-white p-5 shadow-sm">
                <h3 class="text-sm font-semibold text-gray-900 mb-2">My Onboarding</h3>
                <div class="h-2 rounded bg-gray-100 overflow-hidden"><div class="h-2 bg-xerin-600" style="width:{{ $employee->onboarding->progress() }}%"></div></div>
                <ul class="mt-3 space-y-1 text-sm">
                    @foreach ($employee->onboarding->tasks as $task)
                        <li class="{{ $task->status === 'completed' ? 'line-through text-gray-400' : '' }}">{{ $task->title }}</li>
                    @endforeach
                </ul>
            </div>
            @endif
            <div class="rounded-sm border border-gray-200 bg-white p-5 shadow-sm">
                <h3 class="text-sm font-semibold text-gray-900 mb-2">Recent Activity</h3>
                <ul class="space-y-2 text-sm">
                    @forelse ($employee->events as $ev)
                        <li class="border-l-2 border-gray-200 pl-2">{{ $ev->description }} <span class="block text-xs text-gray-400">{{ $ev->created_at->diffForHumans() }}</span></li>
                    @empty
                        <li class="text-gray-400">Nothing yet.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</x-app-layout>
