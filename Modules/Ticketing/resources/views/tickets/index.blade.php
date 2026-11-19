<x-app-layout>
    <x-slot name="header">{{ __('Tickets') }}</x-slot>
    <form method="GET" class="mb-4 flex flex-wrap items-end gap-3">
        <input name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Number or subject" class="rounded-sm border-gray-300 text-sm shadow-sm">
        <select name="status" class="rounded-sm border-gray-300 text-sm"><option value="">Status</option>
            @foreach (\Modules\Ticketing\Models\Ticket::STATUSES as $s)<option value="{{ $s }}" @selected(($filters['status'] ?? '')===$s)>{{ ucwords(str_replace('_',' ',$s)) }}</option>@endforeach
        </select>
        <select name="priority" class="rounded-sm border-gray-300 text-sm"><option value="">Priority</option>
            @foreach (\Modules\Ticketing\Models\Ticket::PRIORITIES as $p)<option value="{{ $p }}" @selected(($filters['priority'] ?? '')===$p)>{{ ucfirst($p) }}</option>@endforeach
        </select>
        <select name="team_id" class="rounded-sm border-gray-300 text-sm"><option value="">Team</option>
            @foreach ($teams as $t)<option value="{{ $t->id }}" @selected(($filters['team_id'] ?? '')==$t->id)>{{ $t->name }}</option>@endforeach
        </select>
        <label class="text-sm flex items-center gap-1"><input type="checkbox" name="mine" value="1" @checked($filters['mine'] ?? false)> Mine</label>
        <label class="text-sm flex items-center gap-1"><input type="checkbox" name="unassigned" value="1" @checked($filters['unassigned'] ?? false)> Unassigned</label>
        <button class="rounded-sm bg-xerin-600 px-4 py-2 text-sm font-semibold text-white">Filter</button>
        <a href="{{ route('ticketing.create') }}" class="rounded-sm border border-gray-300 bg-white px-4 py-2 text-sm font-semibold">+ New Ticket</a>
    </form>
    <div class="rounded-sm border border-gray-200 bg-white shadow-sm overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-xs uppercase text-gray-500"><tr>
                <th class="px-4 py-3 text-left">Ticket</th><th class="px-4 py-3 text-left">Subject</th>
                <th class="px-4 py-3 text-left">Priority</th><th class="px-4 py-3 text-left">Status</th>
                <th class="px-4 py-3 text-left">Agent / Team</th><th class="px-4 py-3 text-left">Created</th>
            </tr></thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($tickets as $t)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-mono text-xs"><a href="{{ route('ticketing.show', $t) }}" class="text-xerin-700 hover:underline">{{ $t->ticket_number }}</a></td>
                        <td class="px-4 py-3 max-w-xs truncate">{{ $t->subject }}</td>
                        <td class="px-4 py-3"><span class="rounded-full px-2 py-0.5 text-xs font-medium {{ ['critical'=>'bg-red-100 text-red-700','urgent'=>'bg-orange-100 text-orange-700','high'=>'bg-amber-100 text-amber-700','normal'=>'bg-blue-100 text-blue-700','low'=>'bg-gray-100 text-gray-600'][$t->priority] }}">{{ ucfirst($t->priority) }}</span></td>
                        <td class="px-4 py-3 text-xs">{{ ucwords(str_replace('_',' ',$t->status)) }}</td>
                        <td class="px-4 py-3 text-xs">{{ $t->agent?->name ?? '—' }} {{ $t->team ? '/ '.$t->team->name : '' }}</td>
                        <td class="px-4 py-3 text-xs text-gray-400">{{ $t->created_at->diffForHumans() }}</td>
                    </tr>
                @empty <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400">No tickets.</td></tr> @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $tickets->links() }}</div>
</x-app-layout>
