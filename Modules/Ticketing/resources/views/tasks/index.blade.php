<x-app-layout>
    <x-slot name="header">{{ __('Task Board') }}</x-slot>

    <div class="mb-4 flex gap-3 items-center">
        <a href="{{ route('ticketing.tasks.index', ['mine' => 1]) }}" class="text-sm {{ request('mine') ? 'font-bold text-xerin-700' : 'text-gray-500' }}">My tasks</a>
        <a href="{{ route('ticketing.tasks.index') }}" class="text-sm {{ request('mine') ? 'text-gray-500' : 'font-bold text-xerin-700' }}">All</a>
        @canany(['task.create','task.assign'])
        <div x-data="{open:false}" class="ml-auto">
            <button @click="open=!open" class="rounded-sm bg-xerin-600 px-4 py-2 text-sm font-semibold text-white">+ New Task</button>
            <form x-show="open" x-cloak method="POST" action="{{ route('ticketing.tasks.store') }}" class="absolute right-0 mt-2 w-96 rounded-sm border bg-white p-4 shadow-lg z-10 space-y-2">
                @csrf
                <input name="title" required placeholder="Title *" class="w-full rounded-sm border-gray-300 text-sm">
                <textarea name="description" placeholder="Description" rows="2" class="w-full rounded-sm border-gray-300 text-sm"></textarea>
                <select name="ticket_id" class="w-full rounded-sm border-gray-300 text-sm"><option value="">Link ticket</option>
                    @foreach ($tickets as $t)<option value="{{ $t->id }}">{{ $t->ticket_number }} — {{ Str::limit($t->subject, 40) }}</option>@endforeach</select>
                <select name="assignee_employee_id" class="w-full rounded-sm border-gray-300 text-sm"><option value="">Assignee</option>
                    @foreach ($employees as $e)<option value="{{ $e->id }}">{{ $e->full_name }}</option>@endforeach</select>
                <input type="date" name="due_date" class="w-full rounded-sm border-gray-300 text-sm">
                <button class="w-full rounded-sm bg-xerin-600 px-4 py-2 text-sm font-semibold text-white">Create</button>
            </form>
        </div>
        @endcanany
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 xl:grid-cols-7 gap-3">
        @foreach ($board as $status => $items)
            <div class="rounded-sm bg-gray-50 border border-gray-200 p-2 min-h-40">
                <h4 class="text-xs font-semibold uppercase text-gray-500 mb-2">{{ str_replace('_',' ',$status) }} ({{ $items->count() }})</h4>
                <div class="space-y-2">
                    @foreach ($items as $task)
                        <div class="rounded-sm border border-gray-200 bg-white p-2 text-xs shadow-sm">
                            <p class="font-medium">{{ $task->title }}</p>
                            <p class="text-gray-400 mt-0.5">{{ $task->ticket?->ticket_number }} · {{ $task->assignee?->full_name ?? 'Unassigned' }}</p>
                            @canany(['task.update','task.assign'])
                            <form method="POST" action="{{ route('ticketing.tasks.status', $task) }}" class="mt-1">
                                @csrf
                                <select name="status" onchange="this.form.submit()" class="w-full text-xs rounded-sm border-gray-200 py-0.5">
                                    @foreach (\Modules\Ticketing\Models\TicketTask::STATUSES as $s)
                                        <option value="{{ $s }}" @selected($task->status === $s)>{{ str_replace('_',' ',$s) }}</option>
                                    @endforeach
                                </select>
                            </form>
                            @endcanany
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</x-app-layout>
