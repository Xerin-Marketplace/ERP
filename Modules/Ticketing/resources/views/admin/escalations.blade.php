<x-app-layout>
    <x-slot name="header">{{ __('Escalation Rules') }}</x-slot>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="lg:col-span-2 rounded-sm border border-gray-200 bg-white shadow-sm overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-xs uppercase text-gray-500"><tr>
                    <th class="px-4 py-3 text-left">Rule</th><th class="px-4 py-3 text-left">Trigger</th>
                    <th class="px-4 py-3 text-left">After</th><th class="px-4 py-3 text-left">Action</th><th class="px-4 py-3 text-left">State</th>
                </tr></thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($rules as $r)
                        <tr><td class="px-4 py-3 font-medium">{{ $r->name }}{{ $r->priority ? ' ('.$r->priority.')' : '' }}</td>
                            <td class="px-4 py-3 text-xs">{{ $r->trigger }}</td>
                            <td class="px-4 py-3 text-xs">{{ $r->after_minutes ? $r->after_minutes.'m' : '—' }}</td>
                            <td class="px-4 py-3 text-xs">{{ collect([$r->to_team_id ? 'team#'.$r->to_team_id : null, $r->to_agent_id ? 'agent#'.$r->to_agent_id : null, $r->new_priority ? 'priority→'.$r->new_priority : null])->filter()->join(', ') ?: 'notify only' }}</td>
                            <td class="px-4 py-3 text-xs">{{ $r->is_active ? 'On' : 'Off' }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="rounded-sm border border-gray-200 bg-white p-4 shadow-sm h-fit">
            <h3 class="text-sm font-semibold mb-3">New Rule</h3>
            <form method="POST" action="{{ route('ticketing.admin.escalations.store') }}" class="space-y-2">
                @csrf
                <input name="name" required placeholder="Name *" class="w-full rounded-sm border-gray-300 text-sm">
                <select name="trigger" required class="w-full rounded-sm border-gray-300 text-sm">
                    @foreach (['sla_breach','unassigned','overdue','critical','warning'] as $tr)<option value="{{ $tr }}">{{ $tr }}</option>@endforeach</select>
                <select name="priority" class="w-full rounded-sm border-gray-300 text-sm"><option value="">Any priority</option>
                    @foreach (\Modules\Ticketing\Models\Ticket::PRIORITIES as $p)<option value="{{ $p }}">{{ ucfirst($p) }}</option>@endforeach</select>
                <input name="after_minutes" type="number" placeholder="After minutes" class="w-full rounded-sm border-gray-300 text-sm">
                <select name="to_team_id" class="w-full rounded-sm border-gray-300 text-sm"><option value="">Reassign to team</option>
                    @foreach ($teams as $t)<option value="{{ $t->id }}">{{ $t->name }}</option>@endforeach</select>
                <select name="notify_user_id" class="w-full rounded-sm border-gray-300 text-sm"><option value="">Notify user</option>
                    @foreach ($users as $u)<option value="{{ $u->id }}">{{ $u->name }}</option>@endforeach</select>
                <select name="new_priority" class="w-full rounded-sm border-gray-300 text-sm"><option value="">Keep priority</option>
                    @foreach (\Modules\Ticketing\Models\Ticket::PRIORITIES as $p)<option value="{{ $p }}">{{ ucfirst($p) }}</option>@endforeach</select>
                <button class="w-full rounded-sm bg-xerin-600 px-4 py-2 text-sm font-semibold text-white">Add Rule</button>
            </form>
        </div>
    </div>
</x-app-layout>
