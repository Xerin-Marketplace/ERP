<x-app-layout>
    <x-slot name="header">Rate Cards & Pricing</x-slot>
    <div class="space-y-4" x-data="{open:false}">
        @can('pricing.manage')
        <button @click="open=!open" class="px-4 py-2 bg-xerin-700 text-white rounded-sm text-sm font-medium">+ New Rate Card</button>
        <form x-show="open" x-cloak method="POST" action="{{ route('commercial.rate-cards.store') }}" class="bg-white rounded-md border border-gray-200 p-5 grid md:grid-cols-4 gap-4">
            @csrf
            <div><label class="text-xs font-medium text-gray-600">Code *</label><input name="code" required placeholder="RC-AE-TZ-AIR" class="mt-1 w-full rounded-sm border-gray-200 text-sm"></div>
            <div><label class="text-xs font-medium text-gray-600">Name *</label><input name="name" required class="mt-1 w-full rounded-sm border-gray-200 text-sm"></div>
            <div><label class="text-xs font-medium text-gray-600">Origin country</label><input name="origin_country" class="mt-1 w-full rounded-sm border-gray-200 text-sm"></div>
            <div><label class="text-xs font-medium text-gray-600">Destination country</label><input name="destination_country" class="mt-1 w-full rounded-sm border-gray-200 text-sm"></div>
            <div><label class="text-xs font-medium text-gray-600">Service type</label><input name="service_type" placeholder="air" class="mt-1 w-full rounded-sm border-gray-200 text-sm"></div>
            <div><label class="text-xs font-medium text-gray-600">Currency *</label><select name="currency" class="mt-1 w-full rounded-sm border-gray-200 text-sm"><option>USD</option><option>AED</option><option>TZS</option></select></div>
            <div><label class="text-xs font-medium text-gray-600">Per-kg rate</label><input type="number" step="0.0001" name="per_kg_rate" min="0" class="mt-1 w-full rounded-sm border-gray-200 text-sm"></div>
            <div><label class="text-xs font-medium text-gray-600">Minimum charge</label><input type="number" step="0.01" name="minimum_charge" min="0" class="mt-1 w-full rounded-sm border-gray-200 text-sm"></div>
            <div><label class="text-xs font-medium text-gray-600">Base rate</label><input type="number" step="0.01" name="base_rate" min="0" class="mt-1 w-full rounded-sm border-gray-200 text-sm"></div>
            <div><label class="text-xs font-medium text-gray-600">Volumetric divisor</label><input type="number" name="volumetric_divisor" value="6000" min="1" class="mt-1 w-full rounded-sm border-gray-200 text-sm"></div>
            <div><label class="text-xs font-medium text-gray-600">Effective from</label><input type="date" name="effective_from" class="mt-1 w-full rounded-sm border-gray-200 text-sm"></div>
            <div><label class="text-xs font-medium text-gray-600">Effective to</label><input type="date" name="effective_to" class="mt-1 w-full rounded-sm border-gray-200 text-sm"></div>
            <div class="md:col-span-4"><button class="px-5 py-2 bg-xerin-700 text-white rounded-sm text-sm">Create (draft)</button></div>
        </form>
        @endcan
        <div class="bg-white rounded-md border border-gray-200 overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-xs text-gray-500 uppercase"><tr><th class="px-5 py-2.5 text-left">Code</th><th class="px-3 py-2.5 text-left">Name</th><th class="px-3 py-2.5 text-left">Route</th><th class="px-3 py-2.5 text-left">Rate</th><th class="px-3 py-2.5 text-left">Status</th><th class="px-5 py-2.5"></th></tr></thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($cards as $c)
                        <tr class="hover:bg-gray-50">
                            <td class="px-5 py-3 font-mono text-xs">{{ $c->code }}</td>
                            <td class="px-3 py-3">{{ $c->name }}<div class="text-xs text-gray-500">{{ $c->service_type }} · v{{ $c->version }}</div></td>
                            <td class="px-3 py-3 text-xs">{{ $c->origin_country ?? 'Any' }} → {{ $c->destination_country ?? 'Any' }}</td>
                            <td class="px-3 py-3 text-xs">{{ $c->per_kg_rate }}/kg · min {{ $c->minimum_charge }} {{ $c->currency }}</td>
                            <td class="px-3 py-3"><span class="px-2 py-0.5 rounded-full text-xs capitalize {{ $c->status==='active'?'bg-emerald-50 text-emerald-700':'bg-gray-100' }}">{{ str_replace('_',' ',$c->status) }}</span></td>
                            <td class="px-5 py-3 text-right">@if($c->status==='draft' && auth()->user()->can('pricing.approve'))<form method="POST" action="{{ route('commercial.rate-cards.approve', $c) }}" class="inline">@csrf<button class="text-xerin-700 text-sm hover:underline">Approve & activate</button></form>@endif</td>
                        </tr>
                    @empty <tr><td colspan="6" class="px-5 py-8 text-center text-gray-400">No rate cards yet.</td></tr> @endforelse
                </tbody>
            </table>
        </div>
        {{ $cards->links() }}
    </div>
</x-app-layout>
