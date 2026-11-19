<x-app-layout>
    <x-slot name="header">{{ __('Shift Management') }}</x-slot>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="lg:col-span-2 space-y-4">
            <div class="rounded-sm border border-gray-200 bg-white shadow-sm overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-500"><tr>
                        <th class="px-4 py-3 text-left">Shift</th><th class="px-4 py-3 text-left">Hours</th>
                        <th class="px-4 py-3 text-left">Break</th><th class="px-4 py-3 text-left">Active</th>
                    </tr></thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($shifts as $s)
                            <tr><td class="px-4 py-3 font-medium">{{ $s->name }} <span class="text-xs text-gray-400">{{ $s->code }}</span>
                                    @if ($s->overnight)<span class="text-xs text-indigo-600">overnight</span>@endif</td>
                                <td class="px-4 py-3">{{ $s->start_time->format('H:i') }}–{{ $s->end_time->format('H:i') }}</td>
                                <td class="px-4 py-3">{{ $s->break_minutes }}m</td>
                                <td class="px-4 py-3">{{ $s->active_count }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="rounded-sm border border-gray-200 bg-white shadow-sm overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-500"><tr>
                        <th class="px-4 py-3 text-left">Employee</th><th class="px-4 py-3 text-left">Shift</th><th class="px-4 py-3 text-left">From</th>
                    </tr></thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($assignments as $a)
                            <tr><td class="px-4 py-3">{{ $a->employee->full_name }}</td>
                                <td class="px-4 py-3">{{ $a->shift->name }}</td>
                                <td class="px-4 py-3">{{ $a->effective_from->format('M j, Y') }}</td></tr>
                        @empty <tr><td colspan="3" class="px-4 py-8 text-center text-gray-400">No assignments.</td></tr> @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="space-y-4">
            @canany(['shift.create','shift.manage'])
            <div class="rounded-sm border border-gray-200 bg-white p-4 shadow-sm">
                <h3 class="text-sm font-semibold mb-2">New Shift</h3>
                <form method="POST" action="{{ route('hr.shifts.store') }}" class="space-y-2">
                    @csrf
                    <input name="code" required placeholder="Code *" class="w-full rounded-sm border-gray-300 text-sm">
                    <input name="name" required placeholder="Name *" class="w-full rounded-sm border-gray-300 text-sm">
                    <div class="flex gap-2"><input type="time" name="start_time" required class="w-full rounded-sm border-gray-300 text-sm"><input type="time" name="end_time" required class="w-full rounded-sm border-gray-300 text-sm"></div>
                    <input name="break_minutes" type="number" value="60" class="w-full rounded-sm border-gray-300 text-sm" placeholder="Break min">
                    <button class="w-full rounded-sm bg-xerin-600 px-4 py-2 text-sm font-semibold text-white">Create</button>
                </form>
            </div>
            @endcanany
            @canany(['shift.assign','shift.manage'])
            <div class="rounded-sm border border-gray-200 bg-white p-4 shadow-sm">
                <h3 class="text-sm font-semibold mb-2">Assign Shift</h3>
                @foreach ($shifts->where('is_active') as $s)
                    <form method="POST" action="{{ route('hr.shifts.assign', $s) }}" class="mb-3 space-y-1 border-b pb-3 last:border-0">
                        @csrf
                        <p class="text-xs font-semibold text-gray-600">{{ $s->name }}</p>
                        <select name="employee_id" required class="w-full rounded-sm border-gray-300 text-sm"><option value="">Employee *</option>
                            @foreach ($employees as $e)<option value="{{ $e->id }}">{{ $e->full_name }}</option>@endforeach</select>
                        <input type="date" name="effective_from" required class="w-full rounded-sm border-gray-300 text-sm">
                        <input type="date" name="effective_to" class="w-full rounded-sm border-gray-300 text-sm">
                        <button class="w-full rounded-sm border border-xerin-600 text-xerin-700 px-4 py-1.5 text-xs font-semibold">Assign</button>
                    </form>
                @endforeach
            </div>
            @endcanany
        </div>
    </div>
</x-app-layout>
