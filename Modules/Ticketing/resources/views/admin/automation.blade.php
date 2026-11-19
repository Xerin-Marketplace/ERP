<x-app-layout>
    <x-slot name="header">{{ __('Automation Rules') }}</x-slot>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="lg:col-span-2 rounded-sm border border-gray-200 bg-white shadow-sm overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-xs uppercase text-gray-500"><tr>
                    <th class="px-4 py-3 text-left">Rule</th><th class="px-4 py-3 text-left">Event</th>
                    <th class="px-4 py-3 text-left">Action</th><th class="px-4 py-3 text-left">Runs</th><th class="px-4 py-3 text-left"></th>
                </tr></thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($rules as $r)
                        <tr><td class="px-4 py-3 font-medium">{{ $r->name }}<p class="text-xs text-gray-400">{{ json_encode($r->conditions) }}</p></td>
                            <td class="px-4 py-3 text-xs">{{ $r->event }}</td>
                            <td class="px-4 py-3 text-xs">{{ $r->action }}</td>
                            <td class="px-4 py-3 text-xs">{{ $r->logs_count }}</td>
                            <td class="px-4 py-3"><form method="POST" action="{{ route('ticketing.admin.automation.toggle', $r) }}">@csrf
                                <button class="text-xs font-semibold {{ $r->is_active ? 'text-red-600' : 'text-green-600' }}">{{ $r->is_active ? 'Disable' : 'Enable' }}</button></form></td></tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="rounded-sm border border-gray-200 bg-white p-4 shadow-sm h-fit">
            <h3 class="text-sm font-semibold mb-3">New Rule</h3>
            <form method="POST" action="{{ route('ticketing.admin.automation.store') }}" class="space-y-2">
                @csrf
                <input name="name" required placeholder="Name *" class="w-full rounded-sm border-gray-300 text-sm">
                <select name="event" required class="w-full rounded-sm border-gray-300 text-sm">
                    <option value="created">On create</option><option value="updated">On update</option><option value="scheduled">Scheduled</option></select>
                <select name="action" required class="w-full rounded-sm border-gray-300 text-sm">
                    @foreach (['assign_team','assign_category_default','set_priority','notify_managers','auto_close'] as $a)<option value="{{ $a }}">{{ $a }}</option>@endforeach</select>
                <select name="priority" class="w-full rounded-sm border-gray-300 text-sm"><option value="">Condition: any priority</option>
                    @foreach (\Modules\Ticketing\Models\Ticket::PRIORITIES as $p)<option value="{{ $p }}">{{ ucfirst($p) }}</option>@endforeach</select>
                <select name="team_id" class="w-full rounded-sm border-gray-300 text-sm"><option value="">Action: team</option>
                    @foreach ($teams as $t)<option value="{{ $t->id }}">{{ $t->name }}</option>@endforeach</select>
                <select name="set_priority" class="w-full rounded-sm border-gray-300 text-sm"><option value="">Action: set priority</option>
                    @foreach (\Modules\Ticketing\Models\Ticket::PRIORITIES as $p)<option value="{{ $p }}">{{ ucfirst($p) }}</option>@endforeach</select>
                <input name="after_hours" type="number" placeholder="auto_close after hours (default 72)" class="w-full rounded-sm border-gray-300 text-sm">
                <button class="w-full rounded-sm bg-xerin-600 px-4 py-2 text-sm font-semibold text-white">Add Rule</button>
            </form>
        </div>
    </div>
</x-app-layout>
