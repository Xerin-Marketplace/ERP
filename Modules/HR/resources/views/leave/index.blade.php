<x-app-layout>
    <x-slot name="header">{{ __('Leave Management') }}</x-slot>
    @if ($balances->isNotEmpty())
    <div class="mb-4 flex gap-3 flex-wrap">
        @foreach ($balances as $b)
            <div class="rounded-sm border border-gray-200 bg-white px-4 py-2 shadow-sm text-sm">
                <span class="text-gray-500">{{ $b->type->code }}</span> <b>{{ $b->available }}</b><span class="text-xs text-gray-400">d left</span>
            </div>
        @endforeach
    </div>
    @endif
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="lg:col-span-2 rounded-sm border border-gray-200 bg-white shadow-sm overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-xs uppercase text-gray-500"><tr>
                    <th class="px-4 py-3 text-left">Ref</th><th class="px-4 py-3 text-left">Employee</th>
                    <th class="px-4 py-3 text-left">Type</th><th class="px-4 py-3 text-left">Dates</th>
                    <th class="px-4 py-3 text-left">Days</th><th class="px-4 py-3 text-left">Status</th><th class="px-4 py-3"></th>
                </tr></thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($requests as $r)
                        <tr>
                            <td class="px-4 py-3 font-medium">{{ $r->reference }}</td>
                            <td class="px-4 py-3">{{ $r->employee->full_name }}</td>
                            <td class="px-4 py-3">{{ $r->type->name }}</td>
                            <td class="px-4 py-3 text-xs">{{ $r->start_date->format('M j') }}–{{ $r->end_date->format('M j') }}</td>
                            <td class="px-4 py-3">{{ $r->days }}</td>
                            <td class="px-4 py-3"><span class="text-xs font-semibold {{ $r->status === 'approved' ? 'text-green-600' : ($r->status === 'rejected' ? 'text-red-600' : 'text-amber-600') }}">{{ ucfirst($r->status) }}</span></td>
                            <td class="px-4 py-3">
                                @if (in_array($r->status, ['pending','approved']) && ($r->employee->user_id === auth()->id() || auth()->user()->can('leave.manage')))
                                    <form method="POST" action="{{ route('hr.leave.cancel', $r) }}">@csrf
                                        <button class="text-xs text-red-600 font-semibold">Cancel</button></form>
                                @endif
                            </td>
                        </tr>
                    @empty <tr><td colspan="7" class="px-4 py-8 text-center text-gray-400">No leave requests.</td></tr> @endforelse
                </tbody>
            </table>
        </div>
        @can('leave.request')
        <div class="rounded-sm border border-gray-200 bg-white p-4 shadow-sm h-fit">
            <h3 class="text-sm font-semibold mb-3">Request Leave</h3>
            <form method="POST" action="{{ route('hr.leave.store') }}" class="space-y-2">
                @csrf
                <select name="leave_type_id" required class="w-full rounded-sm border-gray-300 text-sm"><option value="">Type *</option>
                    @foreach ($types as $t)<option value="{{ $t->id }}">{{ $t->name }} ({{ $t->annual_days }}d/yr)</option>@endforeach</select>
                <input type="date" name="start_date" required class="w-full rounded-sm border-gray-300 text-sm">
                <input type="date" name="end_date" required class="w-full rounded-sm border-gray-300 text-sm">
                <textarea name="reason" placeholder="Reason" rows="2" class="w-full rounded-sm border-gray-300 text-sm"></textarea>
                <button class="w-full rounded-sm bg-xerin-600 px-4 py-2 text-sm font-semibold text-white">Submit</button>
            </form>
        </div>
        @endcan
    </div>
    <div class="mt-4">{{ $requests->links() }}</div>
</x-app-layout>
