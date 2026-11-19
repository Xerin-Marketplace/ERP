<x-app-layout>
    <x-slot name="header">{{ __('Leave Approvals') }}</x-slot>
    <div class="space-y-3">
        @forelse ($pending as $r)
            <div class="rounded-sm border border-gray-200 bg-white p-4 shadow-sm flex items-center justify-between gap-4">
                <div>
                    <p class="font-semibold text-sm">{{ $r->reference }} — {{ $r->employee->full_name }}</p>
                    <p class="text-sm">{{ $r->type->name }} · {{ $r->days }}d · {{ $r->start_date->format('M j') }}–{{ $r->end_date->format('M j') }}</p>
                    @if ($r->reason)<p class="text-xs text-gray-400">{{ $r->reason }}</p>@endif
                </div>
                <div class="flex gap-2" x-data>
                    <form method="POST" action="{{ route('hr.leave.decide', $r) }}">@csrf
                        <input type="hidden" name="decision" value="approved">
                        <button class="rounded-sm bg-green-600 px-4 py-1.5 text-xs font-semibold text-white">Approve</button></form>
                    <form method="POST" action="{{ route('hr.leave.decide', $r) }}">@csrf
                        <input type="hidden" name="decision" value="rejected">
                        <input type="hidden" name="note" :value="prompt('Rejection reason (required):')">
                        <button class="rounded-sm bg-red-600 px-4 py-1.5 text-xs font-semibold text-white">Reject</button></form>
                </div>
            </div>
        @empty
            <div class="rounded-sm border border-gray-200 bg-white p-8 text-center text-gray-400">No pending leave.</div>
        @endforelse
    </div>
    <div class="mt-4">{{ $pending->links() }}</div>
</x-app-layout>
