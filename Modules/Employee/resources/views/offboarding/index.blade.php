<x-app-layout>
    <x-slot name="header">{{ __('Offboarding & Separations') }}</x-slot>

    @if ($canManage)
    <div class="mb-6 rounded-sm border border-gray-200 bg-white p-4 shadow-sm" x-data="{ open: false }">
        <button @click="open = !open" class="text-sm font-semibold text-xerin-700">+ Initiate Separation</button>
        <form x-show="open" x-cloak method="POST" id="offboard-form" class="mt-3 grid grid-cols-1 sm:grid-cols-4 gap-3">
            @csrf
            <select name="employee" required class="rounded-sm border-gray-300 text-sm" form="offboard-form" onchange="this.form.action='{{ url('/employee') }}/'+this.value+'/offboarding'">
                <option value="">Employee *</option>
                @foreach ($employees as $e)<option value="{{ $e->id }}">{{ $e->full_name }} ({{ $e->employee_number }})</option>@endforeach
            </select>
            <select name="reason" required class="rounded-sm border-gray-300 text-sm">
                <option value="">Reason *</option>
                @foreach ($reasons as $r)<option value="{{ $r }}">{{ ucwords(str_replace('_',' ',$r)) }}</option>@endforeach
            </select>
            <input type="date" name="separation_date" required class="rounded-sm border-gray-300 text-sm" title="Separation date">
            <button class="rounded-sm bg-red-600 px-4 py-2 text-sm font-semibold text-white">Initiate</button>
        </form>
    </div>
    @endif

    <div class="rounded-sm border border-gray-200 bg-white shadow-sm overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-xs uppercase text-gray-500"><tr>
                <th class="px-4 py-3 text-left">Employee</th><th class="px-4 py-3 text-left">Reason</th>
                <th class="px-4 py-3 text-left">Separation Date</th><th class="px-4 py-3 text-left">Requested By</th>
                <th class="px-4 py-3 text-left">Access Revoked</th><th class="px-4 py-3 text-left">Status</th>
                @if ($canManage)<th class="px-4 py-3"></th>@endif
            </tr></thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($records as $r)
                    <tr>
                        <td class="px-4 py-3"><a href="{{ route('employee.show', $r->employee) }}" class="text-xerin-700 font-medium hover:underline">{{ $r->employee->full_name }}</a></td>
                        <td class="px-4 py-3">{{ ucwords(str_replace('_',' ',$r->reason)) }}</td>
                        <td class="px-4 py-3">{{ $r->separation_date->toDateString() }}</td>
                        <td class="px-4 py-3 text-xs">{{ $r->requester?->name }}</td>
                        <td class="px-4 py-3 text-xs">{{ $r->access_revoked_at ? 'Yes' : '—' }}</td>
                        <td class="px-4 py-3"><span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $r->status === 'completed' ? 'bg-gray-100 text-gray-600' : ($r->status === 'approved' ? 'bg-blue-100 text-blue-700' : 'bg-amber-100 text-amber-700') }}">{{ ucfirst($r->status) }}</span></td>
                        @if ($canManage)
                            <td class="px-4 py-3">
                                <div class="flex gap-2">
                                    @if ($r->status === 'pending')
                                        <form method="POST" action="{{ route('employee.offboarding.approve', $r) }}">@csrf
                                            <button class="text-xs font-semibold text-green-700 hover:underline">Approve</button></form>
                                    @elseif ($r->status === 'approved')
                                        <form method="POST" action="{{ route('employee.offboarding.complete', $r) }}" onsubmit="return confirm('Complete separation? ERP access will be revoked immediately.')">@csrf
                                            <button class="text-xs font-semibold text-red-700 hover:underline">Complete & Revoke</button></form>
                                    @endif
                                </div>
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-8 text-center text-gray-400">No offboarding records.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $records->links() }}</div>
</x-app-layout>
