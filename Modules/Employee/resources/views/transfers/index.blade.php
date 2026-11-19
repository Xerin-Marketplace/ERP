<x-app-layout>
    <x-slot name="header">{{ __('Employee Transfers') }}</x-slot>

    <form method="GET" class="mb-4 flex gap-3">
        <select name="status" class="rounded-sm border-gray-300 text-sm" onchange="this.form.submit()">
            <option value="">All statuses</option>
            @foreach (['pending','approved','rejected','cancelled'] as $s)
                <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>
            @endforeach
        </select>
    </form>

    <div class="rounded-sm border border-gray-200 bg-white shadow-sm overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-xs uppercase text-gray-500"><tr>
                <th class="px-4 py-3 text-left">Employee</th><th class="px-4 py-3 text-left">Type</th>
                <th class="px-4 py-3 text-left">To</th><th class="px-4 py-3 text-left">Effective</th>
                <th class="px-4 py-3 text-left">Requested By</th><th class="px-4 py-3 text-left">Status</th>
                @if ($canApprove)<th class="px-4 py-3"></th>@endif
            </tr></thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($transfers as $t)
                    <tr>
                        <td class="px-4 py-3"><a href="{{ route('employee.show', $t->employee) }}" class="text-xerin-700 font-medium hover:underline">{{ $t->employee->full_name }}</a></td>
                        <td class="px-4 py-3">{{ ucfirst($t->type) }}</td>
                        <td class="px-4 py-3 text-xs">{{ $t->toCompany?->legal_name ?? '—' }} / {{ $t->toBranch?->name ?? '—' }}<br>{{ $t->toDepartment?->name ?? '' }} {{ $t->toPosition?->title ?? '' }}</td>
                        <td class="px-4 py-3 text-xs">{{ $t->effective_date?->toDateString() ?? '—' }}</td>
                        <td class="px-4 py-3 text-xs">{{ $t->requester?->name }}</td>
                        <td class="px-4 py-3"><span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $t->status === 'approved' ? 'bg-green-100 text-green-700' : ($t->status === 'rejected' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700') }}">{{ ucfirst($t->status) }}</span></td>
                        @if ($canApprove)
                            <td class="px-4 py-3">
                                @if ($t->status === 'pending')
                                    <div class="flex gap-2" x-data>
                                        <form method="POST" action="{{ route('employee.transfers.approve', $t) }}">@csrf
                                            <button class="text-xs font-semibold text-green-700 hover:underline">Approve</button></form>
                                        <form method="POST" action="{{ route('employee.transfers.reject', $t) }}">@csrf
                                            <input type="hidden" name="note" :value="prompt('Rejection reason (required):')">
                                            <button class="text-xs font-semibold text-red-600 hover:underline">Reject</button></form>
                                    </div>
                                @endif
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-8 text-center text-gray-400">No transfer requests.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $transfers->links() }}</div>
</x-app-layout>
