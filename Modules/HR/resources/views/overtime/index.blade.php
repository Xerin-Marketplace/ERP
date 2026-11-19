<x-app-layout>
    <x-slot name="header">{{ __('Overtime') }}</x-slot>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="lg:col-span-2 rounded-sm border border-gray-200 bg-white shadow-sm overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-xs uppercase text-gray-500"><tr>
                    <th class="px-4 py-3 text-left">Ref</th><th class="px-4 py-3 text-left">Employee</th>
                    <th class="px-4 py-3 text-left">Date</th><th class="px-4 py-3 text-left">Planned</th>
                    <th class="px-4 py-3 text-left">Actual</th><th class="px-4 py-3 text-left">Status</th><th class="px-4 py-3"></th>
                </tr></thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($requests as $r)
                        <tr>
                            <td class="px-4 py-3 font-medium">{{ $r->reference }}</td>
                            <td class="px-4 py-3">{{ $r->employee->full_name }}</td>
                            <td class="px-4 py-3">{{ $r->work_date->format('M j') }}</td>
                            <td class="px-4 py-3">{{ $r->planned_hours }}h</td>
                            <td class="px-4 py-3">{{ $r->actual_hours ?? '—' }}</td>
                            <td class="px-4 py-3"><span class="text-xs font-semibold {{ $r->status === 'approved'||$r->status === 'verified' ? 'text-green-600' : ($r->status === 'rejected' ? 'text-red-600' : 'text-amber-600') }}">{{ ucfirst($r->status) }}</span></td>
                            <td class="px-4 py-3 text-right" x-data>
                                @if ($r->status === 'pending' && auth()->user()->can('overtime.approve'))
                                    <span class="flex gap-2 justify-end">
                                        <form method="POST" action="{{ route('hr.overtime.decide', $r) }}">@csrf
                                            <input type="hidden" name="decision" value="approved"><button class="text-xs font-semibold text-green-700">Approve</button></form>
                                        <form method="POST" action="{{ route('hr.overtime.decide', $r) }}">@csrf
                                            <input type="hidden" name="decision" value="rejected">
                                            <input type="hidden" name="note" :value="prompt('Reason:')"><button class="text-xs font-semibold text-red-700">Reject</button></form>
                                    </span>
                                @elseif ($r->status === 'approved' && auth()->user()->can('overtime.manage'))
                                    <form method="POST" action="{{ route('hr.overtime.verify', $r) }}" class="flex gap-1">
                                        @csrf<input type="number" step="0.5" name="actual_hours" placeholder="hrs" class="w-16 rounded-sm border-gray-300 text-xs">
                                        <button class="text-xs font-semibold text-xerin-700">Verify</button></form>
                                @endif
                            </td>
                        </tr>
                    @empty <tr><td colspan="7" class="px-4 py-8 text-center text-gray-400">No overtime.</td></tr> @endforelse
                </tbody>
            </table>
        </div>
        @can('overtime.request')
        <div class="rounded-sm border border-gray-200 bg-white p-4 shadow-sm h-fit">
            <h3 class="text-sm font-semibold mb-2">Request Overtime</h3>
            <form method="POST" action="{{ route('hr.overtime.store') }}" class="space-y-2">
                @csrf
                <input type="date" name="work_date" required class="w-full rounded-sm border-gray-300 text-sm">
                <input type="number" step="0.5" name="planned_hours" required placeholder="Planned hours *" class="w-full rounded-sm border-gray-300 text-sm">
                <textarea name="reason" required placeholder="Reason *" rows="2" class="w-full rounded-sm border-gray-300 text-sm"></textarea>
                <button class="w-full rounded-sm bg-xerin-600 px-4 py-2 text-sm font-semibold text-white">Submit</button>
            </form>
        </div>
        @endcan
    </div>
    <div class="mt-4">{{ $requests->links() }}</div>
</x-app-layout>
