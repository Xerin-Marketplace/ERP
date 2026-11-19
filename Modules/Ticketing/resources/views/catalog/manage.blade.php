<x-app-layout>
    <x-slot name="header">{{ __('Manage Service Catalog') }}</x-slot>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="lg:col-span-2 rounded-sm border border-gray-200 bg-white shadow-sm overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-xs uppercase text-gray-500"><tr>
                    <th class="px-4 py-3 text-left">Service</th><th class="px-4 py-3 text-left">Type / Category</th>
                    <th class="px-4 py-3 text-left">Approvals</th><th class="px-4 py-3 text-left">ETA</th><th class="px-4 py-3 text-left">Status</th>
                </tr></thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($services as $s)
                        <tr><td class="px-4 py-3 font-medium">{{ $s->name }}<p class="text-xs text-gray-400">{{ $s->code }}</p></td>
                            <td class="px-4 py-3 text-xs">{{ $s->type?->name ?? '—' }}<br>{{ $s->category?->name ?? '' }}</td>
                            <td class="px-4 py-3 text-xs">{{ $s->approval_chain ? implode(' → ', $s->approval_chain) : '—' }}</td>
                            <td class="px-4 py-3 text-xs">{{ $s->expected_hours ? $s->expected_hours.'h' : '—' }}</td>
                            <td class="px-4 py-3 text-xs">{{ $s->is_active ? 'Active' : 'Off' }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="rounded-sm border border-gray-200 bg-white p-4 shadow-sm h-fit">
            <h3 class="text-sm font-semibold mb-3">New Service</h3>
            <form method="POST" action="{{ route('ticketing.catalog.store') }}" class="space-y-2 text-sm">
                @csrf
                <input name="code" required placeholder="Code *" class="w-full rounded-sm border-gray-300 text-sm">
                <input name="name" required placeholder="Name *" class="w-full rounded-sm border-gray-300 text-sm">
                <textarea name="description" placeholder="Description" rows="2" class="w-full rounded-sm border-gray-300 text-sm"></textarea>
                <select name="ticket_type_id" class="w-full rounded-sm border-gray-300 text-sm"><option value="">Type</option>
                    @foreach ($types as $t)<option value="{{ $t->id }}">{{ $t->name }}</option>@endforeach</select>
                <select name="category_id" class="w-full rounded-sm border-gray-300 text-sm"><option value="">Category</option>
                    @foreach ($categories as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach</select>
                <select name="default_team_id" class="w-full rounded-sm border-gray-300 text-sm"><option value="">Default team</option>
                    @foreach ($teams as $t)<option value="{{ $t->id }}">{{ $t->name }}</option>@endforeach</select>
                <input name="expected_hours" type="number" placeholder="Expected hours" class="w-full rounded-sm border-gray-300 text-sm">
                <input name="approval_chain" placeholder="Approval chain (e.g. manager,it,finance)" class="w-full rounded-sm border-gray-300 text-sm">
                <label class="text-xs flex gap-1 items-center"><input type="checkbox" name="requires_approval" value="1"> Requires approval</label>
                <label class="text-xs flex gap-1 items-center"><input type="checkbox" name="requires_attachment" value="1"> Requires attachment</label>
                <button class="w-full rounded-sm bg-xerin-600 px-4 py-2 text-sm font-semibold text-white">Add Service</button>
            </form>
        </div>
    </div>
</x-app-layout>
