<x-app-layout>
    <x-slot name="header">{{ __('Compensation') }}</x-slot>
    <div class="mb-4 rounded-sm border border-gray-200 bg-white p-5 shadow-sm">
        <h3 class="text-sm font-semibold text-gray-900 mb-3">Pending Salary Revisions</h3>
        @forelse ($pending as $rev)
            <div class="flex flex-wrap items-center justify-between py-2.5 border-b last:border-0 text-sm">
                <div>
                    <p class="font-medium">{{ $rev->employee->personal->first_name ?? '' }} {{ $rev->employee->personal->last_name ?? $rev->employee->employee_number }}</p>
                    <p class="text-xs text-gray-500">{{ $rev->currency }} {{ number_format($rev->gross(), 2) }} gross · net {{ number_format($rev->net(), 2) }} · effective {{ $rev->effective_date->format('M j, Y') }}</p>
                    <p class="text-xs text-gray-400">Requested by {{ $rev->requester->name ?? '—' }} · {{ $rev->reason }}</p>
                </div>
                <div class="flex gap-2">
                    @can('hr.compensation.approve')
                        <form method="POST" action="{{ route('hr.compensation.approve', $rev) }}">@csrf
                            <button class="rounded-sm bg-green-600 px-3 py-1.5 text-xs font-semibold text-white">Approve</button></form>
                        <form method="POST" action="{{ route('hr.compensation.reject', $rev) }}">@csrf
                            <input type="hidden" name="reason" value="Declined">
                            <button class="rounded-sm bg-red-600 px-3 py-1.5 text-xs font-semibold text-white">Reject</button></form>
                    @endcan
                </div>
            </div>
        @empty
            <p class="text-sm text-gray-400">No pending revisions.</p>
        @endforelse
        <div class="mt-3">{{ $pending->links() }}</div>
    </div>
    <div class="rounded-sm border border-gray-200 bg-white p-5 shadow-sm">
        <h3 class="text-sm font-semibold text-gray-900 mb-3">Recently Approved</h3>
        @forelse ($recent as $rev)
            <div class="flex justify-between py-2 border-b last:border-0 text-sm">
                <span>{{ $rev->employee->employee_number }} — {{ $rev->currency }} {{ number_format($rev->gross(),2) }} · effective {{ $rev->effective_date->format('M j, Y') }}</span>
                <span class="text-xs text-gray-400">{{ $rev->approved_at->diffForHumans() }}</span>
            </div>
        @empty
            <p class="text-sm text-gray-400">None yet.</p>
        @endforelse
    </div>
</x-app-layout>
