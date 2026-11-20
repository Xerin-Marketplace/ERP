<x-app-layout>
    <x-slot name="header">{{ __('IT Support Tickets') }}</x-slot>

    <div class="mb-4 rounded-sm border border-gray-200 bg-white p-4 shadow-sm">
        <form method="GET" class="flex flex-wrap gap-2 items-end">
            <div><label class="block text-xs text-gray-500">Search</label>
                <input name="q" value="{{ request('q') }}" placeholder="Ticket # or subject" class="rounded-sm border-gray-300 text-sm w-56"></div>
            <div><label class="block text-xs text-gray-500">Status</label>
                <select name="status" class="rounded-sm border-gray-300 text-sm"><option value="">All</option>
                    @foreach ($statuses as $s)<option value="{{ $s }}" @selected(request('status')===$s)>{{ ucfirst(str_replace('_',' ',$s)) }}</option>@endforeach</select></div>
            <div><label class="block text-xs text-gray-500">Priority</label>
                <select name="priority" class="rounded-sm border-gray-300 text-sm"><option value="">All</option>
                    @foreach (['low','normal','high','urgent'] as $p)<option value="{{ $p }}" @selected(request('priority')===$p)>{{ ucfirst($p) }}</option>@endforeach</select></div>
            <button class="rounded-sm bg-xerin-600 px-4 py-2 text-sm font-semibold text-white">Filter</button>
            @can('reception.tickets.create')
                <a href="{{ route('reception.tickets.create') }}" class="ml-auto rounded-sm bg-xerin-600 px-4 py-2 text-sm font-semibold text-white">+ New Ticket</a>
            @endcan
        </form>
    </div>

    <div class="rounded-sm border border-gray-200 bg-white shadow-sm overflow-x-auto">
        <table class="w-full text-sm">
            <thead><tr class="text-left text-xs text-gray-500 border-b bg-gray-50">
                <th class="px-4 py-2">Ticket</th><th class="px-4 py-2">Subject</th><th class="px-4 py-2">Category</th>
                <th class="px-4 py-2">Priority</th><th class="px-4 py-2">Technician</th><th class="px-4 py-2">Status</th><th class="px-4 py-2">Created</th>
            </tr></thead>
            <tbody>
                @forelse ($tickets as $t)
                    <tr class="border-b last:border-0 hover:bg-gray-50">
                        <td class="px-4 py-2"><a href="{{ route('reception.tickets.show', $t) }}" class="font-medium text-xerin-700">{{ $t->ticket_number }}</a></td>
                        <td class="px-4 py-2 text-xs max-w-56 truncate">{{ $t->subject }}</td>
                        <td class="px-4 py-2 text-xs text-gray-500">{{ $t->category?->name ?? '—' }}</td>
                        <td class="px-4 py-2 text-xs">{{ ucfirst($t->priority) }}</td>
                        <td class="px-4 py-2 text-xs">{{ $t->assignedAgent?->name ?? 'Unassigned' }}</td>
                        <td class="px-4 py-2"><span class="text-xs rounded-full px-2 py-0.5 {{ in_array($t->status,['resolved','closed']) ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700' }}">{{ ucfirst(str_replace('_',' ',$t->status)) }}</span></td>
                        <td class="px-4 py-2 text-xs text-gray-400">{{ $t->created_at->format('d M H:i') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-6 text-center text-sm text-gray-400">No tickets found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3">{{ $tickets->links() }}</div>
</x-app-layout>
