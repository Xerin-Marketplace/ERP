<x-app-layout>
    <x-slot name="header">{{ $ticket->ticket_number }}</x-slot>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="lg:col-span-2 space-y-4">
            <div class="rounded-sm border border-gray-200 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between">
                    <h3 class="font-semibold">{{ $ticket->subject }}</h3>
                    <span class="text-xs rounded-full px-2 py-0.5 {{ in_array($ticket->status,['resolved','closed']) ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700' }}">{{ ucfirst(str_replace('_',' ',$ticket->status)) }}</span>
                </div>
                <p class="mt-3 text-sm whitespace-pre-line">{{ $ticket->description }}</p>
                <dl class="mt-4 grid grid-cols-2 gap-2 text-xs text-gray-500">
                    <div><dt>Requester</dt><dd class="font-medium text-gray-700">{{ $ticket->requesterEmployee?->full_name ?? $ticket->requester?->name }}</dd></div>
                    <div><dt>Technician</dt><dd class="font-medium text-gray-700">{{ $ticket->assignedAgent?->name ?? 'Unassigned' }}</dd></div>
                    <div><dt>Priority</dt><dd class="font-medium text-gray-700">{{ ucfirst($ticket->priority) }}</dd></div>
                    <div><dt>Category</dt><dd class="font-medium text-gray-700">{{ $ticket->category?->name ?? '—' }}</dd></div>
                    <div><dt>Submitted</dt><dd class="font-medium text-gray-700">{{ $ticket->created_at->format('d M Y H:i') }}</dd></div>
                    <div><dt>Resolved</dt><dd class="font-medium text-gray-700">{{ $ticket->resolved_at?->format('d M Y H:i') ?? '—' }}</dd></div>
                </dl>
            </div>

            <div class="rounded-sm border border-gray-200 bg-white p-5 shadow-sm">
                <h3 class="text-sm font-semibold mb-3">Progress Updates</h3>
                @forelse ($ticket->publicComments as $c)
                    <div class="py-2 border-b last:border-0 text-sm">
                        <p class="text-xs text-gray-400">{{ $c->author?->name }} · {{ $c->created_at->format('d M H:i') }}</p>
                        <p class="mt-0.5">{{ $c->body }}</p>
                    </div>
                @empty
                    <p class="text-sm text-gray-400">No updates yet.</p>
                @endforelse
                @can('reception.tickets.comment')
                    <form method="POST" action="{{ route('reception.tickets.comment', $ticket) }}" class="mt-3 flex gap-2">
                        @csrf
                        <input name="body" required maxlength="2000" placeholder="Add update for the requester…" class="flex-1 rounded-sm border-gray-300 text-sm">
                        <button class="rounded-sm bg-xerin-600 px-4 py-2 text-sm font-semibold text-white">Post</button>
                    </form>
                @endcan
            </div>
        </div>

        <div class="space-y-4">
            <div class="rounded-sm border border-gray-200 bg-white p-5 shadow-sm">
                <h3 class="text-sm font-semibold mb-3">Status History</h3>
                @forelse ($ticket->statusHistories as $h)
                    <div class="py-1.5 border-b last:border-0 text-xs">
                        <span class="text-gray-500">{{ $h->from_status ? ucfirst($h->from_status).' → ' : '' }}</span>
                        <span class="font-medium">{{ ucfirst(str_replace('_',' ',$h->to_status)) }}</span>
                        <span class="text-gray-400 block">{{ \Illuminate\Support\Carbon::parse($h->created_at)->format('d M H:i') }}</span>
                    </div>
                @empty
                    <p class="text-sm text-gray-400">Submitted.</p>
                @endforelse
            </div>

            <div class="rounded-sm border border-gray-200 bg-white p-5 shadow-sm">
                <h3 class="text-sm font-semibold mb-3">Attachments</h3>
                @forelse ($ticket->attachments as $a)
                    <div class="py-1.5 border-b last:border-0 text-xs">
                        <span class="font-medium">{{ $a->file?->original_name ?? 'file' }}</span>
                        <span class="text-gray-400 block">{{ $a->uploader?->name }} · {{ $a->created_at->format('d M H:i') }}</span>
                    </div>
                @empty
                    <p class="text-sm text-gray-400">No attachments.</p>
                @endforelse
                @can('reception.tickets.attachments.upload')
                    <form method="POST" action="{{ route('reception.tickets.attach', $ticket) }}" enctype="multipart/form-data" class="mt-3">
                        @csrf
                        <input type="file" name="file" required accept=".png,.jpg,.jpeg,.pdf,.mp4,.mov" class="text-xs w-full">
                        <button class="mt-2 rounded-sm bg-xerin-600 px-4 py-1.5 text-xs font-semibold text-white">Upload</button>
                    </form>
                @endcan
            </div>
        </div>
    </div>
</x-app-layout>
