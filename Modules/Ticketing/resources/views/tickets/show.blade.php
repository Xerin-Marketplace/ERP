<x-app-layout>
    <x-slot name="header">{{ $ticket->ticket_number }} <span class="text-sm font-normal text-gray-400">· {{ $ticket->subject }}</span></x-slot>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="lg:col-span-2 space-y-4">
            <div class="rounded-sm border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-sm whitespace-pre-wrap">{{ $ticket->description }}</p>
                @if ($ticket->attachments->isNotEmpty())
                    <div class="mt-3 flex flex-wrap gap-2">
                        @foreach ($ticket->attachments as $a)
                            <a href="{{ route('ticketing.attachments.download', $a) }}" class="text-xs rounded-sm border border-gray-300 px-2 py-1 hover:bg-gray-50">{{ $a->file->original_name ?? 'Attachment' }}</a>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Conversation --}}
            <div class="rounded-sm border border-gray-200 bg-white p-5 shadow-sm">
                <h3 class="text-sm font-semibold mb-3">Conversation</h3>
                <ul class="space-y-3 mb-4">
                    @forelse ($ticket->comments->sortBy('created_at') as $c)
                        <li class="border-l-2 {{ $c->is_internal ? 'border-amber-400 bg-amber-50' : 'border-gray-200' }} pl-3 py-1">
                            <p class="text-sm">{{ $c->body }}</p>
                            <p class="text-xs text-gray-400">{{ $c->author?->name }} · {{ $c->created_at->diffForHumans() }}{{ $c->is_internal ? ' · internal' : '' }}</p>
                        </li>
                    @empty <li class="text-sm text-gray-400">No messages yet.</li> @endforelse
                </ul>
                @can('ticket.comment')
                <form method="POST" action="{{ route('ticketing.comment', $ticket) }}" enctype="multipart/form-data" class="space-y-2">
                    @csrf
                    <textarea name="body" required rows="3" class="w-full rounded-sm border-gray-300 text-sm" placeholder="Write a reply..."></textarea>
                    <div class="flex items-center gap-3">
                        <input type="file" name="attachments[]" multiple class="text-xs">
                        @if ($canInternal)<label class="text-xs flex gap-1 items-center"><input type="checkbox" name="internal" value="1"> Internal note</label>@endif
                        <button class="ml-auto rounded-sm bg-xerin-600 px-4 py-1.5 text-xs font-semibold text-white">Post</button>
                    </div>
                </form>
                @endcan
            </div>

            {{-- Tasks --}}
            @if ($ticket->tasks->isNotEmpty())
            <div class="rounded-sm border border-gray-200 bg-white p-5 shadow-sm">
                <h3 class="text-sm font-semibold mb-2">Linked Tasks</h3>
                <ul class="text-sm divide-y divide-gray-100">
                    @foreach ($ticket->tasks as $task)
                        <li class="py-1.5 flex justify-between"><span>{{ $task->task_number }} — {{ $task->title }}</span><span class="text-xs text-gray-400">{{ ucfirst($task->status) }}</span></li>
                    @endforeach
                </ul>
            </div>
            @endif
        </div>

        <div class="space-y-4">
            <div class="rounded-sm border border-gray-200 bg-white p-4 shadow-sm text-sm">
                <dl class="space-y-1.5">
                    <div class="flex justify-between"><dt class="text-gray-500">Status</dt><dd class="font-medium">{{ ucwords(str_replace('_',' ',$ticket->status)) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">Priority</dt><dd>{{ ucfirst($ticket->priority) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">Type</dt><dd>{{ $ticket->type?->name ?? '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">Requester</dt><dd>{{ $ticket->requesterEmployee?->full_name ?? $ticket->requester?->name }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">Agent</dt><dd>{{ $ticket->agent?->name ?? '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">Team</dt><dd>{{ $ticket->team?->name ?? '—' }}</dd></div>
                </dl>
                @if ($ticket->sla)
                    <div class="mt-3 border-t pt-2 text-xs space-y-1">
                        <p class="font-semibold text-gray-700">SLA ({{ $ticket->sla->policy->name }})</p>
                        <p>Response due: {{ $ticket->sla->response_due_at->format('M j H:i') }}
                            @if ($ticket->sla->response_breached) <span class="text-red-600 font-semibold">BREACHED</span> @endif</p>
                        <p>Resolution due: {{ $ticket->sla->resolution_due_at->format('M j H:i') }}
                            @if ($ticket->sla->resolution_breached) <span class="text-red-600 font-semibold">BREACHED</span> @endif</p>
                    </div>
                @endif
            </div>

            @if ($canAssign)
            <div class="rounded-sm border border-gray-200 bg-white p-4 shadow-sm">
                <h3 class="text-sm font-semibold mb-2">Assign</h3>
                <form method="POST" action="{{ route('ticketing.assign', $ticket) }}" class="space-y-2">
                    @csrf
                    <select name="agent_id" class="w-full rounded-sm border-gray-300 text-sm"><option value="">Agent</option>
                        @foreach ($agents as $a)<option value="{{ $a->id }}" @selected($ticket->assigned_agent_id === $a->id)>{{ $a->name }}</option>@endforeach</select>
                    <select name="team_id" class="w-full rounded-sm border-gray-300 text-sm"><option value="">Team</option>
                        @foreach ($teams as $t)<option value="{{ $t->id }}" @selected($ticket->assigned_team_id === $t->id)>{{ $t->name }}</option>@endforeach</select>
                    <input name="reason" placeholder="Reason" class="w-full rounded-sm border-gray-300 text-sm">
                    <button class="w-full rounded-sm bg-xerin-600 px-4 py-1.5 text-sm font-semibold text-white">Assign</button>
                </form>
            </div>
            @endif

            <div class="rounded-sm border border-gray-200 bg-white p-4 shadow-sm">
                <h3 class="text-sm font-semibold mb-2">Actions</h3>
                <div class="flex flex-wrap gap-2">
                    @foreach (['in_progress' => 'Start Work', 'pending' => 'Hold', 'resolved' => 'Resolve', 'closed' => 'Close', 'reopened' => 'Reopen'] as $to => $label)
                        @if ($ticket->canTransitionTo($to))
                            <form method="POST" action="{{ route('ticketing.transition', [$ticket, $to]) }}">@csrf
                                <button class="rounded-sm border border-gray-300 px-3 py-1.5 text-xs font-semibold hover:bg-gray-50">{{ $label }}</button></form>
                        @endif
                    @endforeach
                </div>
            </div>

            @if ($ticket->approvals->isNotEmpty())
            <div class="rounded-sm border border-gray-200 bg-white p-4 shadow-sm">
                <h3 class="text-sm font-semibold mb-2">Approvals</h3>
                <ul class="text-xs space-y-1.5">
                    @foreach ($ticket->approvals as $a)
                        <li class="flex justify-between"><span>Step {{ $a->step }}: {{ ucwords(str_replace('_',' ',$a->approver_role)) }}</span>
                            <span class="font-medium {{ $a->status === 'approved' ? 'text-green-600' : ($a->status === 'rejected' ? 'text-red-600' : 'text-amber-600') }}">{{ ucfirst($a->status) }}</span></li>
                    @endforeach
                </ul>
            </div>
            @endif

            <div class="rounded-sm border border-gray-200 bg-white p-4 shadow-sm">
                <h3 class="text-sm font-semibold mb-2">Timeline</h3>
                <ul class="text-xs space-y-1.5">
                    @foreach ($ticket->statusHistories as $h)
                        <li>{{ $h->from_status ? $h->from_status.' → ' : '' }}<strong>{{ $h->to_status }}</strong>
                            <span class="text-gray-400">· {{ $h->actor?->name ?? 'system' }} · {{ $h->created_at->diffForHumans() }}</span></li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</x-app-layout>
