<x-app-layout>
    <x-slot name="header">{{ __('Attendance') }}</x-slot>
    <form method="GET" class="mb-4 flex gap-2 flex-wrap">
        <input type="date" name="date" value="{{ request('date') }}" class="rounded-sm border-gray-300 text-sm">
        <select name="status" class="rounded-sm border-gray-300 text-sm"><option value="">All statuses</option>
            @foreach (['present','absent','late','half_day','on_leave','holiday','rest_day','pending_verification'] as $s)
                <option value="{{ $s }}" @selected(request('status')===$s)>{{ ucwords(str_replace('_',' ',$s)) }}</option>
            @endforeach</select>
        <button class="rounded-sm bg-xerin-600 px-4 py-2 text-sm font-semibold text-white">Filter</button>
    </form>
    @if ($pendingCorrections->isNotEmpty())
        <div class="mb-4 rounded-sm border border-amber-200 bg-amber-50 p-4">
            <h3 class="text-sm font-semibold mb-2">Pending Corrections</h3>
            @foreach ($pendingCorrections as $c)
                <div class="flex items-center justify-between py-1.5 border-b border-amber-100 last:border-0 text-sm">
                    <span>{{ $c->reference }} — {{ $c->employee->full_name }} · {{ $c->work_date->format('M j') }} · {{ str_replace('_',' ',$c->type) }}</span>
                    <span class="flex gap-1" x-data>
                        <form method="POST" action="{{ route('hr.attendance.corrections.decide', $c) }}">@csrf
                            <input type="hidden" name="decision" value="approved"><button class="text-xs font-semibold text-green-700">Approve</button></form>
                        <form method="POST" action="{{ route('hr.attendance.corrections.decide', $c) }}">@csrf
                            <input type="hidden" name="decision" value="rejected">
                            <input type="hidden" name="note" :value="prompt('Reason:')"><button class="text-xs font-semibold text-red-700">Reject</button></form>
                    </span>
                </div>
            @endforeach
        </div>
    @endif
    <div class="rounded-sm border border-gray-200 bg-white shadow-sm overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-xs uppercase text-gray-500"><tr>
                <th class="px-4 py-3 text-left">Employee</th><th class="px-4 py-3 text-left">Date</th>
                <th class="px-4 py-3 text-left">In</th><th class="px-4 py-3 text-left">Out</th>
                <th class="px-4 py-3 text-left">Worked</th><th class="px-4 py-3 text-left">Status</th>
            </tr></thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($records as $r)
                    <tr>
                        <td class="px-4 py-3">{{ $r->employee->full_name }}<p class="text-xs text-gray-400">{{ $r->employee->employee_number }}</p></td>
                        <td class="px-4 py-3">{{ $r->work_date->format('M j') }}</td>
                        <td class="px-4 py-3">{{ $r->clock_in_at?->format('H:i') ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $r->clock_out_at?->format('H:i') ?? '—' }}</td>
                        <td class="px-4 py-3">{{ round($r->worked_minutes/60, 1) }}h</td>
                        <td class="px-4 py-3"><span class="text-xs font-semibold {{ $r->status === 'late' ? 'text-amber-600' : ($r->status === 'absent' ? 'text-red-600' : 'text-green-600') }}">{{ ucwords(str_replace('_',' ',$r->status)) }}</span></td>
                    </tr>
                @empty <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400">No attendance records.</td></tr> @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $records->links() }}</div>
    <div class="mt-4 rounded-sm border border-gray-200 bg-white p-4 shadow-sm max-w-xl">
        <h3 class="text-sm font-semibold mb-2">Request Correction</h3>
        <form method="POST" action="{{ route('hr.attendance.corrections.store') }}" class="grid grid-cols-2 gap-2 text-sm">
            @csrf
            <input type="date" name="work_date" required class="rounded-sm border-gray-300 text-sm">
            <select name="type" required class="rounded-sm border-gray-300 text-sm">
                <option value="missing_clock_in">Missing clock-in</option><option value="missing_clock_out">Missing clock-out</option>
                <option value="time_adjustment">Time adjustment</option><option value="other">Other</option></select>
            <input type="time" name="requested_clock_in" class="rounded-sm border-gray-300 text-sm" title="Requested in">
            <input type="time" name="requested_clock_out" class="rounded-sm border-gray-300 text-sm" title="Requested out">
            <textarea name="reason" required placeholder="Reason" class="col-span-2 rounded-sm border-gray-300 text-sm"></textarea>
            <button class="col-span-2 rounded-sm bg-xerin-600 px-4 py-2 text-sm font-semibold text-white">Submit</button>
        </form>
    </div>
</x-app-layout>
