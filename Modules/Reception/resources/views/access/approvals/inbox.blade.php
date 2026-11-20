<x-app-layout>
    <x-slot name="header">Approval Inbox</x-slot>
    <div class="space-y-3">
        @forelse ($tasks as $t)
            <div class="rounded-sm border border-gray-200 bg-white p-5 shadow-sm">
                <div class="flex flex-wrap justify-between gap-3">
                    <div>
                        <a href="{{ route('reception.access.requests.show', $t->request) }}" class="font-mono text-xs text-xerin-700 font-semibold">{{ $t->request->reference }}</a>
                        <p class="text-sm font-medium mt-1">{{ $t->request->full_name }} — <span class="text-xs text-gray-500">{{ str_replace('_',' ',$t->request->type) }}</span></p>
                        <p class="text-xs text-gray-400 mt-0.5">Stage: {{ $t->stage->name ?? $t->sequence }} · role: {{ $t->approver_role }}
                            @if ($t->due_at) · due {{ $t->due_at->diffForHumans() }} @endif</p>
                        <p class="text-xs text-gray-500 mt-1">{{ $t->request->justification }}</p>
                    </div>
                    <form method="POST" action="{{ route('reception.access.approvals.decide', $t) }}" class="flex flex-col gap-2 min-w-48">
                        @csrf
                        <input name="comment" placeholder="Comment (optional)" class="rounded-sm border-gray-300 text-xs">
                        <div class="flex gap-2">
                            <button name="decision" value="approved" class="flex-1 rounded-sm bg-green-700 px-3 py-1.5 text-xs font-semibold text-white">Approve</button>
                            <button name="decision" value="changes_requested" class="flex-1 rounded-sm bg-amber-600 px-3 py-1.5 text-xs font-semibold text-white">Changes</button>
                            <button name="decision" value="rejected" class="flex-1 rounded-sm bg-red-700 px-3 py-1.5 text-xs font-semibold text-white">Reject</button>
                        </div>
                    </form>
                </div>
            </div>
        @empty
            <p class="rounded-sm border border-gray-200 bg-white p-8 text-center text-sm text-gray-400">No pending approvals for your roles.</p>
        @endforelse
    </div>
</x-app-layout>
