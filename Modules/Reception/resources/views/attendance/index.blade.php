<x-app-layout>
    <x-slot name="header">{{ __('Attendance Monitoring') }}</x-slot>

    <div class="mb-4 rounded-sm border border-gray-200 bg-white p-4 shadow-sm">
        <form method="GET" class="flex flex-wrap gap-2 items-end">
            <div><label class="block text-xs text-gray-500">Date</label>
                <input type="date" name="date" value="{{ $date }}" class="rounded-sm border-gray-300 text-sm"></div>
            <div><label class="block text-xs text-gray-500">Department</label>
                <select name="department_id" class="rounded-sm border-gray-300 text-sm">
                    <option value="">All departments</option>
                    @foreach ($departments as $d)
                        <option value="{{ $d->id }}" @selected(request('department_id') == $d->id)>{{ $d->name }}</option>
                    @endforeach</select></div>
            <button class="rounded-sm bg-xerin-600 px-4 py-2 text-sm font-semibold text-white">Filter</button>
            @can('reception.attendance.export')
                <a href="{{ route('reception.attendance.export', ['date' => $date]) }}" class="rounded-sm border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700">Export CSV</a>
            @endcan
        </form>
    </div>

    <div class="rounded-sm border border-gray-200 bg-white shadow-sm overflow-x-auto">
        <table class="w-full text-sm">
            <thead><tr class="text-left text-xs text-gray-500 border-b bg-gray-50">
                <th class="px-4 py-2">Employee</th><th class="px-4 py-2">Number</th><th class="px-4 py-2">Department</th>
                <th class="px-4 py-2">Clock In</th><th class="px-4 py-2">Clock Out</th><th class="px-4 py-2">Late</th><th class="px-4 py-2">Status</th>
            </tr></thead>
            <tbody>
                @forelse ($records as $r)
                    <tr class="border-b last:border-0 hover:bg-gray-50">
                        <td class="px-4 py-2 font-medium">{{ $r->employee?->full_name }}</td>
                        <td class="px-4 py-2 text-xs text-gray-500">{{ $r->employee?->employee_number }}</td>
                        <td class="px-4 py-2 text-xs text-gray-500">{{ $r->employee?->department?->name ?? '—' }}</td>
                        <td class="px-4 py-2 text-xs">{{ $r->clock_in_at?->format('H:i') ?? '—' }}</td>
                        <td class="px-4 py-2 text-xs">{{ $r->clock_out_at?->format('H:i') ?? '—' }}</td>
                        <td class="px-4 py-2 text-xs">{{ $r->late_minutes > 0 ? $r->late_minutes.'m' : '—' }}</td>
                        <td class="px-4 py-2"><span class="text-xs rounded-full px-2 py-0.5 {{ $r->status==='late' ? 'bg-amber-100 text-amber-700' : ($r->status==='absent' ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700') }}">{{ ucfirst(str_replace('_',' ',$r->status)) }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-6 text-center text-sm text-gray-400">No attendance records for {{ $date }}.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <p class="mt-2 text-xs text-gray-400">Read-only view — attendance corrections are managed by HR.</p>
</x-app-layout>
