<x-app-layout>
    <x-slot name="header">Leads & Pipeline</x-slot>
    <div class="space-y-4" x-data="{open:false}">
        <div class="flex flex-wrap items-center gap-3">
            <form method="GET" class="flex gap-2 flex-1 min-w-60">
                <input name="q" value="{{ request('q') }}" placeholder="Search leads…" class="flex-1 rounded-sm border-gray-200 text-sm">
                <select name="stage" class="rounded-sm border-gray-200 text-sm"><option value="">All stages</option>@foreach($stages as $s)<option value="{{ $s }}" @selected(request('stage')===$s)>{{ ucfirst(str_replace('_',' ',$s)) }}</option>@endforeach</select>
                <button class="px-4 py-2 bg-xerin-600 text-white rounded-sm text-sm">Filter</button>
            </form>
            @can('lead.create')<button @click="open=!open" class="px-4 py-2 bg-xerin-700 text-white rounded-sm text-sm font-medium">+ New Lead</button>@endcan
        </div>
        <form x-show="open" x-cloak method="POST" action="{{ route('commercial.leads.store') }}" class="bg-white rounded-md border border-gray-200 p-5 grid md:grid-cols-3 gap-4">
            @csrf
            <div><label class="text-xs font-medium text-gray-600">Name *</label><input name="name" required class="mt-1 w-full rounded-sm border-gray-200 text-sm"></div>
            <div><label class="text-xs font-medium text-gray-600">Company</label><input name="company" class="mt-1 w-full rounded-sm border-gray-200 text-sm"></div>
            <div><label class="text-xs font-medium text-gray-600">Phone</label><input name="phone" class="mt-1 w-full rounded-sm border-gray-200 text-sm"></div>
            <div><label class="text-xs font-medium text-gray-600">Email</label><input type="email" name="email" class="mt-1 w-full rounded-sm border-gray-200 text-sm"></div>
            <div><label class="text-xs font-medium text-gray-600">Source</label><select name="source" class="mt-1 w-full rounded-sm border-gray-200 text-sm"><option value="">—</option>@foreach(\Modules\Commercial\Models\Lead::SOURCES as $s)<option value="{{ $s }}">{{ ucfirst(str_replace('_',' ',$s)) }}</option>@endforeach</select></div>
            <div><label class="text-xs font-medium text-gray-600">Interested service</label><input name="interested_service" class="mt-1 w-full rounded-sm border-gray-200 text-sm"></div>
            <div><label class="text-xs font-medium text-gray-600">Origin</label><input name="origin" class="mt-1 w-full rounded-sm border-gray-200 text-sm"></div>
            <div><label class="text-xs font-medium text-gray-600">Destination</label><input name="destination" class="mt-1 w-full rounded-sm border-gray-200 text-sm"></div>
            <div><label class="text-xs font-medium text-gray-600">Priority</label><select name="priority" class="mt-1 w-full rounded-sm border-gray-200 text-sm"><option value="normal">Normal</option><option value="high">High</option><option value="low">Low</option></select></div>
            <div class="md:col-span-3"><button class="px-5 py-2 bg-xerin-700 text-white rounded-sm text-sm">Create Lead</button></div>
        </form>
        <div class="bg-white rounded-md border border-gray-200 overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-xs text-gray-500 uppercase"><tr><th class="px-5 py-2.5 text-left">Ref</th><th class="px-3 py-2.5 text-left">Lead</th><th class="px-3 py-2.5 text-left">Source</th><th class="px-3 py-2.5 text-left">Stage</th><th class="px-3 py-2.5 text-left">Owner</th><th class="px-5 py-2.5 text-left">Actions</th></tr></thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($leads as $l)
                        <tr class="hover:bg-gray-50">
                            <td class="px-5 py-3 font-mono text-xs">{{ $l->reference }}</td>
                            <td class="px-3 py-3"><div class="font-medium">{{ $l->name }}</div>@if($l->company)<div class="text-xs text-gray-500">{{ $l->company }}</div>@endif</td>
                            <td class="px-3 py-3 capitalize">{{ $l->source ?: '—' }}</td>
                            <td class="px-3 py-3"><span class="px-2 py-0.5 rounded-full text-xs capitalize {{ $l->stage==='won'?'bg-emerald-50 text-emerald-700':($l->stage==='lost'?'bg-red-50 text-red-600':'bg-gray-100') }}">{{ str_replace('_',' ',$l->stage) }}</span></td>
                            <td class="px-3 py-3 text-xs">{{ $l->owner?->name ?? '—' }}</td>
                            <td class="px-5 py-3">
                                @can('lead.update')
                                <form method="POST" action="{{ route('commercial.leads.transition', $l) }}" class="inline-flex gap-1">@csrf
                                    <select name="stage" class="rounded-sm border-gray-200 text-xs py-1">@foreach($stages as $s)<option value="{{ $s }}" @selected($l->stage===$s)>{{ ucfirst(str_replace('_',' ',$s)) }}</option>@endforeach</select>
                                    <button class="px-2 py-1 bg-gray-700 text-white rounded-sm text-xs">Set</button>
                                </form>
                                @if(!$l->converted_customer_id)<form method="POST" action="{{ route('commercial.leads.convert', $l) }}" class="inline" onsubmit="return confirm('Convert lead to customer?')">@csrf<button class="text-xerin-700 text-xs hover:underline">Convert</button></form>@endif
                                @endcan
                            </td>
                        </tr>
                    @empty <tr><td colspan="6" class="px-5 py-8 text-center text-gray-400">No leads found.</td></tr> @endforelse
                </tbody>
            </table>
        </div>
        {{ $leads->links() }}
    </div>
</x-app-layout>
