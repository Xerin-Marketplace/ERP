<x-app-layout>
    <x-slot name="header">{{ __('Approval Center') }}</x-slot>
    <div class="space-y-3">
        @forelse ($pending as $a)
            <div class="rounded-sm border border-gray-200 bg-white p-4 shadow-sm flex items-center justify-between gap-4">
                <div>
                    <a href="{{ route('ticketing.show', $a->ticket) }}" class="font-semibold text-xerin-700 hover:underline">{{ $a->ticket->ticket_number }}</a>
                    <p class="text-sm">{{ $a->ticket->subject }}</p>
                    <p class="text-xs text-gray-400">Step {{ $a->step }} · {{ ucwords(str_replace('_',' ',$a->approver_role)) }} · requested by {{ $a->ticket->requester?->name }}</p>
                </div>
                <div class="flex gap-2" x-data>
                    <form method="POST" action="{{ route('ticketing.approvals.decide', $a) }}">@csrf
                        <input type="hidden" name="decision" value="approved">
                        <button class="rounded-sm bg-green-600 px-4 py-1.5 text-xs font-semibold text-white">Approve</button></form>
                    <form method="POST" action="{{ route('ticketing.approvals.decide', $a) }}">@csrf
                        <input type="hidden" name="decision" value="rejected">
                        <input type="hidden" name="note" :value="prompt('Rejection reason (required):')">
                        <button class="rounded-sm bg-red-600 px-4 py-1.5 text-xs font-semibold text-white">Reject</button></form>
                </div>
            </div>
        @empty
            <div class="rounded-sm border border-gray-200 bg-white p-8 text-center text-gray-400 shadow-sm">No pending approvals.</div>
        @endforelse
    </div>
</x-app-layout>
