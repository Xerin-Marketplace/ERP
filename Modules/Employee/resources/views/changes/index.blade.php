<x-app-layout>
    <x-slot name="header">{{ __('Promotions & Employment Changes') }}</x-slot>

    <form method="GET" class="mb-4 flex gap-3">
        <select name="type" class="rounded-sm border-gray-300 text-sm" onchange="this.form.submit()">
            <option value="">All types</option>
            @foreach (\Modules\Employee\Models\ChangeRequest::TYPES as $t)
                <option value="{{ $t }}" @selected(request('type') === $t)>{{ ucwords(str_replace('_',' ',$t)) }}</option>
            @endforeach
        </select>
        <select name="status" class="rounded-sm border-gray-300 text-sm" onchange="this.form.submit()">
            <option value="">All statuses</option>
            @foreach (['pending','approved','rejected'] as $s)
                <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>
            @endforeach
        </select>
    </form>

    <div class="rounded-sm border border-gray-200 bg-white shadow-sm overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-xs uppercase text-gray-500"><tr>
                <th class="px-4 py-3 text-left">Employee</th><th class="px-4 py-3 text-left">Change</th>
                <th class="px-4 py-3 text-left">New Values</th><th class="px-4 py-3 text-left">Effective</th>
                <th class="px-4 py-3 text-left">Initiated By</th><th class="px-4 py-3 text-left">Status</th>
                @if ($canApprove)<th class="px-4 py-3"></th>@endif
            </tr></thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($changes as $c)
                    <tr>
                        <td class="px-4 py-3"><a href="{{ route('employee.show', $c->employee) }}" class="text-xerin-700 font-medium hover:underline">{{ $c->employee->full_name }}</a></td>
                        <td class="px-4 py-3">{{ ucwords(str_replace('_',' ',$c->type)) }}</td>
                        <td class="px-4 py-3 text-xs font-mono">{{ json_encode($c->new_values) }}</td>
                        <td class="px-4 py-3 text-xs">{{ $c->effective_date?->toDateString() ?? '—' }}</td>
                        <td class="px-4 py-3 text-xs">{{ $c->initiator?->name }}</td>
                        <td class="px-4 py-3"><span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $c->status === 'approved' ? 'bg-green-100 text-green-700' : ($c->status === 'rejected' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700') }}">{{ ucfirst($c->status) }}</span></td>
                        @if ($canApprove)
                            <td class="px-4 py-3">
                                @if ($c->status === 'pending')
                                    <div class="flex gap-2" x-data>
                                        <form method="POST" action="{{ route('employee.changes.approve', $c) }}">@csrf
                                            <button class="text-xs font-semibold text-green-700 hover:underline">Approve</button></form>
                                        <form method="POST" action="{{ route('employee.changes.reject', $c) }}">@csrf
                                            <input type="hidden" name="note" :value="prompt('Rejection reason (required):')">
                                            <button class="text-xs font-semibold text-red-600 hover:underline">Reject</button></form>
                                    </div>
                                @endif
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-8 text-center text-gray-400">No change requests.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $changes->links() }}</div>
</x-app-layout>
